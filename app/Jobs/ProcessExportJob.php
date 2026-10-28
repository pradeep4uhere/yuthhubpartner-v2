<?php

namespace App\Jobs;

use App\Models\Export;
use App\Services\Exports\LearnerExporter;
use App\Services\Exports\FieldAgentExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Exports\PartnerLearnerExporter;
use App\Services\Exports\PartnerFieldAgentExporter;
use App\Services\Exports\PartnerEventTransactionExporter;
use App\Models\Partner;
use Throwable;

class ProcessExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $exportId
    ) {
    }

    public function handle(
        LearnerExporter $learnerExporter,
        FieldAgentExporter $fieldAgentExporter,
        PartnerLearnerExporter $partnerLearnerExporter,
        PartnerFieldAgentExporter $partnerFieldAgentExporter,
        PartnerEventTransactionExporter $partnerEventTransactionExporter
    ): void {
        $export = Export::findOrFail($this->exportId);

        $export->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {

            $result = match ($export->type) {

               'learners' => $export->requestedBy instanceof \App\Models\Partner
                            ? $partnerLearnerExporter->export($export)
                            : $learnerExporter->export($export),


               'field_agents' => $export->requestedBy instanceof \App\Models\Partner
                            ? $partnerFieldAgentExporter->export($export)
                            : $fieldAgentExporter->export($export),


              'transactions' => $export->requestedBy instanceof Partner
                            ? $partnerEventTransactionExporter->export($export)
                            : throw new \RuntimeException(
                                'Transaction exports are currently supported for partners only.'
                            ),


                default => throw new \RuntimeException(
                    'Unsupported export type: ' . $export->type
                ),

            };

            $export->update([
                'status' => 'completed',
                'file_name' => $result['file_name'],
                'file_path' => $result['file_path'],
                'total_records' => $result['total_records'],
                'completed_at' => now(),
                'error_message' => null,
            ]);

            logger()->info(
                'Central export completed.',
                [
                    'export_id' => $export->id,
                    'type' => $export->type,
                    'total_records' => $result['total_records'],
                    'file_path' => $result['file_path'],
                ]
            );

        } catch (Throwable $e) {

            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            logger()->error(
                'Central export failed.',
                [
                    'export_id' => $export->id,
                    'type' => $export->type,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Export::where('id', $this->exportId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
