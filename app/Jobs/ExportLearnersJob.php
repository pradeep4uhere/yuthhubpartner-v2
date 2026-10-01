<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Learner;
use App\Models\LearnerExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportLearnersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum execution time for large exports.
     */
    public int $timeout = 1800;

    /**
     * Maximum attempts.
     */
    public int $tries = 3;

    /**
     * Backoff between retries.
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $exportId,
        public int $centralExportId
    ) {
    }

    public function handle(): void
    {
        $export = LearnerExport::findOrFail(
            $this->exportId
        );

        $centralExport = Export::find(
            $this->centralExportId
        );

        /*
        |--------------------------------------------------------------------------
        | Processing
        |--------------------------------------------------------------------------
        */

        $export->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        if ($centralExport) {
            $centralExport->update([
                'status' => 'processing',
                'started_at' => now(),
                'error_message' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | File
        |--------------------------------------------------------------------------
        */

        $fileName = sprintf(
            'learners_%s_%d.csv',
            now()->format('Ymd_His'),
            $export->id
        );

        $directory = 'learner-exports';

        Storage::disk('local')->makeDirectory(
            $directory
        );

        $filePath = $directory . '/' . $fileName;

        $absolutePath = Storage::disk('local')->path(
            $filePath
        );

        $handle = null;

        try {

            /*
            |--------------------------------------------------------------------------
            | Open File
            |--------------------------------------------------------------------------
            */

            $handle = fopen(
                $absolutePath,
                'w'
            );

            if ($handle === false) {
                throw new \RuntimeException(
                    'Unable to create export file.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UTF-8 BOM
            |--------------------------------------------------------------------------
            */

            fwrite(
                $handle,
                "\xEF\xBB\xBF"
            );

            /*
            |--------------------------------------------------------------------------
            | CSV Header
            |--------------------------------------------------------------------------
            */

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
            |--------------------------------------------------------------------------
            | Learner Query
            |--------------------------------------------------------------------------
            */

            $query = Learner::query()
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'gender',
                    'phone_number',
                    'normalized_mobile',
                    'program_code',
                    'partner_id',
                    'partner_center_id',
                    'field_agent_id',
                    'city',
                    'state',
                    'course_completed',
                    'status',
                    'created_at',
                ])
                ->with([
                    'partner:id,name',
                    'partnerCenter:id,name',
                    'fieldAgent:id,name,sakhi_id',
                ]);

            $this->applyFilters(
                $query,
                $export->filters ?? []
            );

            /*
            |--------------------------------------------------------------------------
            | Export in Chunks
            |--------------------------------------------------------------------------
            */

            $totalRecords = 0;

            $query
                ->orderBy('id')
                ->chunkById(
                    5000,
                    function ($learners) use (
                        $handle,
                        &$totalRecords,
                        $export,
                        $centralExport
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

                        /*
                        |--------------------------------------------------------------------------
                        | Existing Learner Export Progress
                        |--------------------------------------------------------------------------
                        */

                        $export->update([
                            'total_records' => $totalRecords,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Central Export Progress
                        |--------------------------------------------------------------------------
                        */

                        if ($centralExport) {
                            $centralExport->update([
                                'total_records' => $totalRecords,
                            ]);
                        }
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Close File
            |--------------------------------------------------------------------------
            */

            fclose($handle);

            $handle = null;

            /*
            |--------------------------------------------------------------------------
            | Existing Learner Export Completed
            |--------------------------------------------------------------------------
            */

            $export->update([
                'status' => 'completed',
                'file_name' => $fileName,
                'file_path' => $filePath,
                'total_records' => $totalRecords,
                'completed_at' => now(),
                'error_message' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Central Export Completed
            |--------------------------------------------------------------------------
            */

            if ($centralExport) {
                $centralExport->update([
                    'status' => 'completed',
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'total_records' => $totalRecords,
                    'completed_at' => now(),
                    'error_message' => null,
                ]);
            }

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Close File
            |--------------------------------------------------------------------------
            */

            if (is_resource($handle)) {
                fclose($handle);
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Partial File
            |--------------------------------------------------------------------------
            */

            Storage::disk('local')->delete(
                $filePath
            );

            /*
            |--------------------------------------------------------------------------
            | Existing Learner Export Failed
            |--------------------------------------------------------------------------
            */

            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Central Export Failed
            |--------------------------------------------------------------------------
            */

            if ($centralExport) {
                $centralExport->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Queue failure handler.
     */
    public function failed(Throwable $exception): void
    {
        /*
        |--------------------------------------------------------------------------
        | Existing Learner Export
        |--------------------------------------------------------------------------
        */

        LearnerExport::where(
            'id',
            $this->exportId
        )->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Central Export
        |--------------------------------------------------------------------------
        */

        Export::where(
            'id',
            $this->centralExportId
        )->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    */

    private function applyFilters(
        $query,
        array $filters
    ): void {

        if (!empty($filters['search'])) {

            $search = trim(
                $filters['search']
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'first_name',
                    'like',
                    "{$search}%"
                )
                ->orWhere(
                    'last_name',
                    'like',
                    "{$search}%"
                )
                ->orWhere(
                    'normalized_mobile',
                    'like',
                    "{$search}%"
                )
                ->orWhere(
                    'program_code',
                    'like',
                    "{$search}%"
                );
            });
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

        if (
            isset($filters['course_completed']) &&
            $filters['course_completed'] !== ''
        ) {

            $query->where(
                'course_completed',
                $filters['course_completed']
            );
        }

        if (!empty($filters['status'])) {

            $query->where(
                'status',
                $filters['status']
            );
        }

        if (!empty($filters['program_code'])) {

            $query->where(
                'program_code',
                'like',
                trim(
                    $filters['program_code']
                ) . '%'
            );
        }

        if (!empty($filters['date_from'])) {

            $query->where(
                'created_at',
                '>=',
                $filters['date_from'] . ' 00:00:00'
            );
        }

        if (!empty($filters['date_to'])) {

            $query->where(
                'created_at',
                '<=',
                $filters['date_to'] . ' 23:59:59'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CSV Security
    |--------------------------------------------------------------------------
    */

    private function csvValue(
        mixed $value
    ): string {

        $value = (string) ($value ?? '');

        /*
        | Prevent CSV formula injection in Excel.
        */

        if (
            $value !== '' &&
            in_array(
                $value[0],
                ['=', '+', '-', '@'],
                true
            )
        ) {
            return "'" . $value;
        }

        return $value;
    }
}