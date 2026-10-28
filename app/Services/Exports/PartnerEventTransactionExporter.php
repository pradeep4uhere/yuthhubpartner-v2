<?php

namespace App\Services\Exports;

use App\Models\Export;
use App\Models\EventTransaction;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

class PartnerEventTransactionExporter
{
    /**
     * Generate an XLSX file containing every matching event transaction
     * belonging exclusively to the partner who requested the export.
     */
    public function export(Export $export): array
    {
        $partner = $export->requestedBy;

        if (!$partner instanceof Partner) {
            throw new RuntimeException(
                'Partner event transaction exports require a partner requester.'
            );
        }

        $query = EventTransaction::query()
            ->where('partner_id', $partner->id);

        $this->applyFilters($query, $export->filters ?? []);

        $directory = 'exports/partner-event-transactions';

        $fileName = 'partner-event-transactions-' .
            $export->id . '-' . now()->format('YmdHis') . '.xlsx';

        $filePath = $directory . '/' . $fileName;

        Storage::disk('local')->makeDirectory($directory);

        $spreadsheet = new Spreadsheet();

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Event Transactions');

            $headers = [
                'Transaction ID',
                'Learner ID',
                'Beneficiary Name',
                'Beneficiary Mobile',
                'Event Type',
                'Event Category',
                'Event Master Type ID',
                'Event Value',
                'Submitted Date',
                'Review Status',
                'Workflow Status',
                'Created At',
            ];

            /*
             * Write column headers using coordinate-based cell references.
             */
            foreach ($headers as $index => $header) {
                $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';

                $sheet->setCellValue($cell, $header);
            }

            /*
             * Style the header row and configure worksheet navigation.
             */
            $sheet->getStyle('A1:L1')->getFont()->setBold(true);

            $sheet->getStyle('A1:L1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FF101E43');

            $sheet->getStyle('A1:L1')->getFont()
                ->getColor()
                ->setARGB('FFFFFFFF');

            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:L1');

            $row = 2;
            $totalRecords = 0;

            /*
             * Fetch records in chunks to avoid loading the entire result
             * set into application memory at once.
             */
            $query
                ->select([
                    'id',
                    'learner_id',
                    'beneficiary_name',
                    'beneficiary_phone_number',
                    'event_type_name',
                    'event_category_name',
                    'event_master_type_id',
                    'event_value',
                    'event_date_submitted',
                    'review_status',
                    'workflow_status',
                    'created_at',
                ])
                ->chunkById(1000, function ($transactions) use (
                    $sheet,
                    &$row,
                    &$totalRecords
                ) {
                    foreach ($transactions as $transaction) {
                        $values = [
                            $transaction->id,
                            $transaction->learner_id,
                            $transaction->beneficiary_name,
                            $transaction->beneficiary_phone_number,
                            $transaction->event_type_name,
                            $transaction->event_category_name,
                            $transaction->event_master_type_id,
                            $transaction->event_value,
                            $transaction->event_date_submitted,
                            $transaction->review_status,
                            $transaction->workflow_status,
                            $transaction->created_at,
                        ];

                        foreach ($values as $index => $value) {
                            $cell = Coordinate::stringFromColumnIndex(
                                $index + 1
                            ) . $row;

                            /*
                             * Keep event values numeric in Excel so that
                             * users can calculate totals and apply formats.
                             */
                            if ($index === 7 && is_numeric($value)) {
                                $sheet->setCellValue(
                                    $cell,
                                    (float) $value
                                );

                                continue;
                            }

                            /*
                             * Store all other values explicitly as text.
                             * This prevents untrusted text from being
                             * interpreted as an Excel formula.
                             */
                            $text = $value === null
                                ? ''
                                : (string) $value;

                            $sheet->setCellValueExplicit(
                                $cell,
                                $text,
                                DataType::TYPE_STRING
                            );
                        }

                        $row++;
                        $totalRecords++;
                    }
                }, 'id');

            /*
             * Automatically size all exported columns for readability.
             */
            foreach (range('A', 'L') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $fullPath = Storage::disk('local')->path($filePath);

            (new Xlsx($spreadsheet))->save($fullPath);

            return [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'total_records' => $totalRecords,
            ];
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($filePath);

            throw $exception;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Apply the main search and advanced filters to the export query.
     */
    protected function applyFilters(
        Builder $query,
        array $filters
    ): void {
        /*
         * Main search: beneficiary name, mobile, event type, category,
         * or transaction ID when the search term contains only digits.
         */
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $subQuery) use ($search) {
                $subQuery
                    ->where('beneficiary_name', 'LIKE', "%{$search}%")
                    ->orWhere(
                        'beneficiary_phone_number',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere('event_type_name', 'LIKE', "%{$search}%")
                    ->orWhere(
                        'event_category_name',
                        'LIKE',
                        "%{$search}%"
                    );

                if (ctype_digit($search)) {
                    $subQuery->orWhere('id', $search);
                }
            });
        }

        /*
         * Apply partial-match filters.
         */
        foreach ([
            'beneficiary_name',
            'beneficiary_phone_number',
            'event_type_name',
            'event_category_name',
        ] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where(
                    $field,
                    'LIKE',
                    '%' . trim((string) $filters[$field]) . '%'
                );
            }
        }

        /*
         * Apply exact-match filters.
         */
        foreach ([
            'event_master_type_id',
            'review_status',
            'workflow_status',
        ] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }

        /*
         * Apply inclusive submitted-date boundaries.
         */
        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate(
                'event_date_submitted',
                '>=',
                $filters['date_from']
            );
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate(
                'event_date_submitted',
                '<=',
                $filters['date_to']
            );
        }
    }
}
