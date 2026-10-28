<?php

namespace App\Services\Exports;

use App\Models\Export;
use App\Models\Partner;
use App\Services\ExportScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class PartnerFieldAgentExporter
{
    public function __construct(
        protected ExportScopeService $scopeService
    ) {
    }

    /**
     * Generate a formatted Excel export exclusively for Partner field agents.
     */
    public function export(Export $export): array
    {
        $requester = $export->requestedBy;

        if (!$requester instanceof Partner) {
            throw new \RuntimeException(
                'Partner field agent export requires an authenticated Partner requester.'
            );
        }

        $query = $this->scopeService->fieldAgentQuery($requester);

        $this->applyFilters($query, $export->filters ?? []);

        $query->with([
            'partner:id,name',
            'partnerCenter:id,name',
        ]);

        $spreadsheet = new Spreadsheet();
        $filePath = null;

        try {
            $dataSheet = $spreadsheet->getActiveSheet();
            $dataSheet->setTitle('Field Agents');

            $totalRecords = $this->buildFieldAgentSheet(
                $dataSheet,
                $query
            );

            $summarySheet = $spreadsheet->createSheet();
            $summarySheet->setTitle('Performance Summary');

            $this->buildSummarySheet(
                $summarySheet,
                $totalRecords
            );

            $fileName = sprintf(
                'partner_field_agents_%s_%d.xlsx',
                now()->format('Ymd_His'),
                $export->id
            );

            $directory = 'exports/partner-field-agents';

            Storage::disk('local')->makeDirectory($directory);

            $filePath = $directory . '/' . $fileName;

            $writer = new Xlsx($spreadsheet);
            $writer->save(
                Storage::disk('local')->path($filePath)
            );

            return [
                'file_name' => $fileName,
                'file_path' => $filePath,
                'total_records' => $totalRecords,
            ];
        } catch (Throwable $exception) {
            if ($filePath !== null) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Apply the Partner listing filters without allowing another Partner's scope.
     */
    protected function applyFilters(
        Builder $query,
        array $filters
    ): void {
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('csc_id', 'like', "%{$search}%")
                    ->orWhere('sakhi_id', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (!empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $centerId = $filters['center_id']
            ?? $filters['partner_center_id']
            ?? null;

        if ($centerId !== null && $centerId !== '') {
            $query->where('partner_center_id', $centerId);
        }
    }

    /**
     * Create the formatted Field Agents worksheet and return the exported row count.
     */
    protected function buildFieldAgentSheet(
        Worksheet $sheet,
        Builder $query
    ): int {
        $sheet->mergeCells('A1:T1');
        $sheet->setCellValue(
            'A1',
            'YOUTHHUB PARTNER | FIELD AGENT REPORT'
        );

        $sheet->mergeCells('A2:T2');
        $sheet->setCellValue(
            'A2',
            'Generated on ' . now()->format('d M Y, h:i A')
        );

        $sheet->mergeCells('A3:J3');
        $sheet->setCellValue('A3', 'FIELD AGENT DETAILS');

        $sheet->mergeCells('K3:O3');
        $sheet->setCellValue('K3', 'JOBS');

        $sheet->mergeCells('P3:T3');
        $sheet->setCellValue('P3', 'SOCIAL PROTECTION');

        $headers = [
            'SN',
            'Field Agent Name',
            'Agent ID',
            'Mobile Number',
            'Email',
            'Partner Name',
            'Partner Center',
            'State',
            'District',
            'Agent Status',
            'Total',
            'Submitted',
            'Action Required',
            'Accepted',
            'Rejected',
            'Total',
            'Submitted',
            'Action Required',
            'Accepted',
            'Rejected',
        ];

        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                $index + 1
            );

            $sheet->setCellValue($column . '4', $header);
        }

        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:T4');

        $row = 5;
        $serial = 0;

        $query->orderBy('id')->chunkById(
            1000,
            function ($agents) use ($sheet, &$row, &$serial) {
                $agentIds = $agents->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $countsByAgent = $this->getTransactionCounts($agentIds);

                foreach ($agents as $agent) {
                    $counts = $countsByAgent[(int) $agent->id] ?? [];

                    $serial++;

                    $values = [
                        $serial,
                        $this->safeCell($agent->name),
                        $this->safeCell(
                            $agent->csc_id ?: $agent->sakhi_id
                        ),
                        $this->safeCell(
                            $agent->contact_number
                                ?? $agent->phone_number
                                ?? $agent->mobile
                        ),
                        $this->safeCell($agent->email),
                        $this->safeCell($agent->partner?->name),
                        $this->safeCell($agent->partnerCenter?->name),
                        $this->safeCell($agent->state),
                        $this->safeCell($agent->district),
                        $this->safeCell(ucfirst((string) $agent->status)),
                        $counts['jobs_total'] ?? 0,
                        $counts['jobs_submitted'] ?? 0,
                        $counts['jobs_required'] ?? 0,
                        $counts['jobs_accepted'] ?? 0,
                        $counts['jobs_rejected'] ?? 0,
                        $counts['social_total'] ?? 0,
                        $counts['social_submitted'] ?? 0,
                        $counts['social_required'] ?? 0,
                        $counts['social_accepted'] ?? 0,
                        $counts['social_rejected'] ?? 0,
                    ];

                    foreach ($values as $index => $value) {
                        $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                            $index + 1
                        );

                        $sheet->setCellValue(
                            $column . $row,
                            $value
                        );
                    }

                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:T{$row}")
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID);

                        $sheet->getStyle("A{$row}:T{$row}")
                            ->getFill()
                            ->getStartColor()
                            ->setARGB('FFF3F6FB');
                    }

                    // Accepted, action-required and rejected status columns.
                    foreach (['N', 'S'] as $column) {
                        $sheet->getStyle("{$column}{$row}")
                            ->getFont()->getColor()->setARGB('FF16803C');
                    }

                    foreach (['M', 'R'] as $column) {
                        $sheet->getStyle("{$column}{$row}")
                            ->getFont()->getColor()->setARGB('FFB45309');
                    }

                    foreach (['O', 'T'] as $column) {
                        $sheet->getStyle("{$column}{$row}")
                            ->getFont()->getColor()->setARGB('FFDC2626');
                    }

                    $row++;
                }
            }
        );

        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF101E43'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2:T2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['argb' => 'FF64748B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);

        foreach (['A3:J3', 'K3:O3', 'P3:T3'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2865DC'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        $sheet->getStyle('A4:T4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF2865DC'],
                ],
            ],
        ]);

        if ($row > 5) {
            $sheet->getStyle('A5:T' . ($row - 1))
                ->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_HAIR);
        }

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(34);

        foreach (range('A', 'T') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(28);
        $sheet->getColumnDimension('M')->setWidth(19);
        $sheet->getColumnDimension('R')->setWidth(19);

        return $serial;
    }

    /**
     * Aggregate transaction counts for a batch of field agents.
     *
     * Verify the event type IDs and status rules against the existing listing
     * controller before production use.
     */
    protected function getTransactionCounts(array $agentIds): array
    {
        if (empty($agentIds)) {
            return [];
        }

        $rows = DB::table('event_transactions')
            ->select('field_agent_id')
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id IN (1, 2, 5)
                    THEN 1 ELSE 0 END) AS jobs_total
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id IN (1, 2, 5)
                    AND workflow_status = 'submitted'
                    THEN 1 ELSE 0 END) AS jobs_submitted
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id IN (1, 2, 5)
                    AND review_status = 'action_required'
                    THEN 1 ELSE 0 END) AS jobs_required
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id IN (1, 2, 5)
                    AND review_status = 'accepted'
                    THEN 1 ELSE 0 END) AS jobs_accepted
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id IN (1, 2, 5)
                    AND review_status = 'rejected'
                    THEN 1 ELSE 0 END) AS jobs_rejected
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id = 3
                    THEN 1 ELSE 0 END) AS social_total
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id = 3
                    AND workflow_status = 'submitted'
                    THEN 1 ELSE 0 END) AS social_submitted
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id = 3
                    AND review_status = 'action_required'
                    THEN 1 ELSE 0 END) AS social_required
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id = 3
                    AND review_status = 'accepted'
                    THEN 1 ELSE 0 END) AS social_accepted
            ")
            ->selectRaw("
                SUM(CASE WHEN event_master_type_id = 3
                    AND review_status = 'rejected'
                    THEN 1 ELSE 0 END) AS social_rejected
            ")
            ->whereIn('field_agent_id', $agentIds)
            ->groupBy('field_agent_id')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $result[(int) $row->field_agent_id] = [
                'jobs_total' => (int) $row->jobs_total,
                'jobs_submitted' => (int) $row->jobs_submitted,
                'jobs_required' => (int) $row->jobs_required,
                'jobs_accepted' => (int) $row->jobs_accepted,
                'jobs_rejected' => (int) $row->jobs_rejected,
                'social_total' => (int) $row->social_total,
                'social_submitted' => (int) $row->social_submitted,
                'social_required' => (int) $row->social_required,
                'social_accepted' => (int) $row->social_accepted,
                'social_rejected' => (int) $row->social_rejected,
            ];
        }

        return $result;
    }

    /**
     * Build the Performance Summary worksheet from the exported agent totals.
     */
    protected function buildSummarySheet(
        Worksheet $sheet,
        int $totalAgents
    ): void {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue(
            'A1',
            'YOUTHHUB PARTNER | PERFORMANCE SUMMARY'
        );

        $sheet->setCellValue('A3', 'Metric');
        $sheet->setCellValue('B3', 'Jobs');
        $sheet->setCellValue('C3', 'Social Protection');
        $sheet->setCellValue('D3', 'Combined');

        $sheet->setCellValue('A4', 'Total Field Agents');
        $sheet->setCellValue('B4', $totalAgents);
        $sheet->setCellValue('C4', $totalAgents);
        $sheet->setCellValue('D4', $totalAgents);

        $sheet->setCellValue('A6', 'Transaction Metrics');
        $sheet->mergeCells('A6:D6');

        $sheet->setCellValue('A7', 'Metric');
        $sheet->setCellValue('B7', 'Jobs');
        $sheet->setCellValue('C7', 'Social Protection');
        $sheet->setCellValue('D7', 'Combined');

        $sheet->setCellValue('A8', 'Total Transactions');
        $sheet->setCellValue('A9', 'Submitted');
        $sheet->setCellValue('A10', 'Action Required');
        $sheet->setCellValue('A11', 'Accepted');
        $sheet->setCellValue('A12', 'Rejected');

        // Values are linked to the detail sheet so the summary stays consistent.
        $columns = [
            8 => ['K', 'P'],
            9 => ['L', 'Q'],
            10 => ['M', 'R'],
            11 => ['N', 'S'],
            12 => ['O', 'T'],
        ];

        foreach ($columns as $row => [$jobsColumn, $socialColumn]) {
            $sheet->setCellValue(
                "B{$row}",
                "=SUM('Field Agents'!{$jobsColumn}:{$jobsColumn})"
            );

            $sheet->setCellValue(
                "C{$row}",
                "=SUM('Field Agents'!{$socialColumn}:{$socialColumn})"
            );

            $sheet->setCellValue("D{$row}", "=B{$row}+C{$row}");
        }

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 15,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF101E43'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (['A3:D3', 'A7:D7', 'A6:D6'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2865DC'],
                ],
            ]);
        }

        $sheet->getStyle('A3:D12')
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A4');
    }

    /**
     * Protect text cells against accidental Excel formula execution.
     */
    protected function safeCell(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
