<?php

namespace App\Services\Exports;

use App\Models\Export;
use App\Services\ExportScopeService;
use Illuminate\Support\Facades\Storage;

class FieldAgentExporter
{
    public function __construct(
        protected ExportScopeService $scopeService
    ) {
    }

    /**
     * Generate field agent export.
     */
    public function export(Export $export): array
    {
        /*
         * Resolve requester from polymorphic relationship.
         */
        $requester = $export->requestedBy;

        if (!$requester) {
            throw new \RuntimeException(
                'Export requester could not be found.'
            );
        }

        /*
         * Get field agents according to requester scope.
         */
        $query = $this->scopeService->fieldAgentQuery(
            $requester
        );

        /*
         * Apply additional filters.
         */
        $this->applyFilters(
            $query,
            $export->filters ?? []
        );

        /*
         * Load required relationships.
         */
        $query->with([
            'partner:id,name',
            'partnerCenter:id,name',
            'placementPartnerUser:id,name',
        ]);

        $fileName = sprintf(
            'field_agents_%s_%d.csv',
            now()->format('Ymd_His'),
            $export->id
        );

        $directory = 'exports/field-agents';

        Storage::disk('local')->makeDirectory(
            $directory
        );

        $filePath = $directory . '/' . $fileName;

        $absolutePath = Storage::disk('local')->path(
            $filePath
        );

        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException(
                'Unable to create field agent export file.'
            );
        }

        $totalRecords = 0;

        try {

            /*
             * UTF-8 BOM for Excel compatibility.
             */
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Name',
                'Email',
                'Mobile',
                'Sakhi ID',
                'Partner',
                'Partner Center',
                'Placement Partner',
                'Status',
                'Created At',
            ]);

            /*
             * Process large datasets in chunks.
             */
            $query->orderBy('id')->chunkById(
                5000,
                function ($fieldAgents) use (
                    $handle,
                    &$totalRecords
                ) {

                    foreach ($fieldAgents as $fieldAgent) {

                        fputcsv($handle, [
                            $fieldAgent->id,

                            $this->csvValue(
                                $fieldAgent->name
                            ),

                            $this->csvValue(
                                $fieldAgent->email
                            ),

                            $this->csvValue(
                                $fieldAgent->phone_number
                                    ?? $fieldAgent->mobile
                                    ?? null
                            ),

                            $this->csvValue(
                                $fieldAgent->sakhi_id
                            ),

                            $this->csvValue(
                                $fieldAgent->partner?->name
                            ),

                            $this->csvValue(
                                $fieldAgent->partnerCenter?->name
                            ),

                            $this->csvValue(
                                $fieldAgent
                                    ->placementPartnerUser
                                    ?->name
                            ),

                            ucfirst(
                                (string) $fieldAgent->status
                            ),

                            optional(
                                $fieldAgent->created_at
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
     * Apply additional filters.
     */
    protected function applyFilters(
    $query,
        array $filters
    ): void {

        if (!empty($filters['search'])) {

            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('csc_id', 'like', "%{$search}%")
                    ->orWhere('sakhi_id', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

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

        if (!empty($filters['placement_partner_user_id'])) {
            $query->where(
                'placement_partner_user_id',
                $filters['placement_partner_user_id']
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
