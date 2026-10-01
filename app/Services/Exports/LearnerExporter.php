<?php

namespace App\Services\Exports;

use App\Models\Export;
use App\Services\ExportScopeService;
use Illuminate\Support\Facades\Storage;

class LearnerExporter
{
    public function __construct(
        protected ExportScopeService $scopeService
    ) {
    }

    /**
     * Generate learner export.
     */
    public function export(Export $export): array
    {
        /*
         * Resolve the person who requested the export.
         */
        $requester = $export->requestedBy;

        if (!$requester) {
            throw new \RuntimeException(
                'Export requester could not be found.'
            );
        }

        /*
         * Get only the learners this requester
         * is allowed to export.
         */
        $query = $this->scopeService->learnerQuery(
            $requester
        );

        /*
         * Apply additional export filters.
         */
        $this->applyFilters(
            $query,
            $export->filters ?? []
        );

        $fileName = sprintf(
            'learners_%s_%d.csv',
            now()->format('Ymd_His'),
            $export->id
        );

        $directory = 'exports/learners';

        Storage::disk('local')->makeDirectory($directory);

        $filePath = $directory . '/' . $fileName;

        $absolutePath = Storage::disk('local')->path(
            $filePath
        );

        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException(
                'Unable to create learner export file.'
            );
        }

        $totalRecords = 0;

        try {

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'First Name',
                'Last Name',
                'Gender',
                'Mobile',
                'Program Code',
                'Partner',
                'Partner Center',
                'Field Agent',
                'Sakhi ID',
                'City',
                'State',
                'Course Status',
                'Status',
                'Created At',
            ]);

            /*
             * Relationships required by CSV.
             */
            $query->with([
                'partner:id,name',
                'partnerCenter:id,name',
                'fieldAgent:id,name,sakhi_id',
            ]);

            /*
             * Process large datasets safely.
             */
            $query->orderBy('id')->chunkById(
                5000,
                function ($learners) use (
                    $handle,
                    &$totalRecords
                ) {

                    foreach ($learners as $learner) {

                        fputcsv($handle, [
                            $learner->id,

                            $this->csvValue(
                                $learner->first_name
                            ),

                            $this->csvValue(
                                $learner->last_name
                            ),

                            $this->csvValue(
                                $learner->gender
                            ),

                            $this->csvValue(
                                $learner->normalized_mobile
                                    ?: $learner->phone_number
                            ),

                            $this->csvValue(
                                $learner->program_code
                            ),

                            $this->csvValue(
                                $learner->partner?->name
                            ),

                            $this->csvValue(
                                $learner->partnerCenter?->name
                            ),

                            $this->csvValue(
                                $learner->fieldAgent?->name
                            ),

                            $this->csvValue(
                                $learner->fieldAgent?->sakhi_id
                            ),

                            $this->csvValue(
                                $learner->city
                            ),

                            $this->csvValue(
                                $learner->state
                            ),

                            $learner->course_completed
                                ? 'Completed'
                                : 'Pending',

                            ucfirst(
                                (string) $learner->status
                            ),

                            optional(
                                $learner->created_at
                            )->format(
                                'Y-m-d H:i:s'
                            ),
                        ]);

                        $totalRecords++;
                    }

                    fflush($handle);
                }
            );

            fclose($handle);

        } catch (\Throwable $e) {

            if (is_resource($handle)) {
                fclose($handle);
            }

            Storage::disk('local')->delete(
                $filePath
            );

            throw $e;
        }

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Apply user-selected filters.
     */
    protected function applyFilters(
        $query,
        array $filters
    ): void {

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (!empty($filters['partner_id'])) {
            $query->where(
                'partner_id',
                $filters['partner_id']
            );
        }

        if (!empty($filters['partner_center_id'])) {
            $query->where(
                'partner_center_id',
                $filters['partner_center_id']
            );
        }

        if (!empty($filters['field_agent_id'])) {
            $query->where(
                'field_agent_id',
                $filters['field_agent_id']
            );
        }

        if (!empty($filters['program_code'])) {
            $query->where(
                'program_code',
                $filters['program_code']
            );
        }
    }

    /**
     * Safely prepare CSV values.
     */
    protected function csvValue(mixed $value): string
    {
        return trim(
            (string) ($value ?? '')
        );
    }
}
