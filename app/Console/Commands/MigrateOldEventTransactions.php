<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldEventTransactions extends Command
{
    protected $signature = 'migrate:old-event-transactions
                            {--chunk=1000 : Number of records per chunk}
                            {--dry-run : Run migration without inserting records}';

    protected $description = 'Migrate old event_transactions data into the new event_transactions table';

    private string $oldConnection = 'old_mysql';
    private string $newConnection = 'mysql';

    private array $reviewStatusMap = [
        'Accepted' => 'approved',
        'Rejected' => 'rejected',
        'Pending'  => 'under_review',
        'Open'     => 'under_review',
        'Return'   => 'returned',
    ];

    private array $unknownFieldTypes = [];
    private array $unknownIndustryTypes = [];
    private array $unknownReviewStatuses = [];

    private array $fieldTypeMap = [];
    private array $industryTypeMap = [];

    private int $total = 0;
    private int $migrated = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        if ($chunkSize <= 0) {
            $this->error('Chunk size must be greater than 0.');
            return self::FAILURE;
        }

        $this->info('==============================================');
        $this->info(' Old Event Transactions Migration');
        $this->info('==============================================');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be inserted.');
        }

        $this->info("Chunk Size: {$chunkSize}");
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Test Database Connections
        |--------------------------------------------------------------------------
        */

        try {
            DB::connection($this->oldConnection)->getPdo();
            $this->info('✓ Old database connection successful.');

            DB::connection($this->newConnection)->getPdo();
            $this->info('✓ New database connection successful.');
        } catch (Throwable $e) {
            $this->error('Database connection failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Load Master Data
        |--------------------------------------------------------------------------
        */

        $this->loadMasterData();

        /*
        |--------------------------------------------------------------------------
        | Old Records Count
        |--------------------------------------------------------------------------
        */

        $oldQuery = DB::connection($this->oldConnection)
            ->table('event_transactions');

        $this->total = $oldQuery->count();

        $this->info("Total Old Records: {$this->total}");
        $this->newLine();

        if ($this->total === 0) {
            $this->warn('No records found in old event_transactions table.');
            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Migration
        |--------------------------------------------------------------------------
        */

        $processed = 0;

        $oldQuery
            ->orderBy('id')
            ->chunkById($chunkSize, function ($records) use ($dryRun, &$processed) {

                foreach ($records as $old) {
                    $processed++;

                    try {
                        $this->migrateRecord($old, $dryRun);
                    } catch (Throwable $e) {
                        $this->failed++;

                        $this->error(
                            "Failed ID {$old->id}: " . $e->getMessage()
                        );
                    }
                }

                $this->output->write(
                    "\rProgress: {$processed}/{$this->total}"
                );
            }, 'id');

        $this->newLine(2);

        /*
        |--------------------------------------------------------------------------
        | Final Report
        |--------------------------------------------------------------------------
        */

        $this->displaySummary($dryRun);

        return $this->failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Load Field Types / Industry Types
    |--------------------------------------------------------------------------
    */

   private function loadMasterData(): void
    {
        $this->info('Loading field_types...');

        $this->fieldTypeMap = DB::connection($this->newConnection)
            ->table('field_types')
            ->get(['id', 'name'])
            ->mapWithKeys(function ($row) {
                return [
                    mb_strtolower(trim($row->name)) => $row->id
                ];
            })
            ->toArray();

        $this->info('Field Types Loaded: ' . count($this->fieldTypeMap));

        $this->info('Loading industry_types...');

        $this->industryTypeMap = DB::connection($this->newConnection)
            ->table('industry_types')
            ->get(['id', 'name'])
            ->mapWithKeys(function ($row) {
                return [
                    mb_strtolower(trim($row->name)) => $row->id
                ];
            })
            ->toArray();

        $this->info('Industry Types Loaded: ' . count($this->industryTypeMap));
    }

    /*
    |--------------------------------------------------------------------------
    | Migrate Single Record
    |--------------------------------------------------------------------------
    */

    private function migrateRecord(object $old, bool $dryRun): void
    {
        /*
        |--------------------------------------------------------------------------
        | Duplicate Check
        |--------------------------------------------------------------------------
        */

        $exists = DB::connection($this->newConnection)
            ->table('event_transactions')
            ->where('id', $old->id)
            ->exists();

        if ($exists) {
            $this->skipped++;
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Review Status
        |--------------------------------------------------------------------------
        */

        $reviewStatus = null;

        if (!is_null($old->review_status)) {

            $oldReviewStatus = trim($old->review_status);

            if (array_key_exists($oldReviewStatus, $this->reviewStatusMap)) {
                $reviewStatus = $this->reviewStatusMap[$oldReviewStatus];
            } else {
                $this->unknownReviewStatuses[$oldReviewStatus] = true;

                /*
                |--------------------------------------------------------------------------
                | Preserve unknown status instead of silently losing it
                |--------------------------------------------------------------------------
                */

                $reviewStatus = $oldReviewStatus;
            }
        }

         /*
        |--------------------------------------------------------------------------
        | Field Type / Industry Type - FIXED
        |--------------------------------------------------------------------------
        */

        $fieldTypeId = null;
        $industryTypeId = null;

        $oldFieldType = $this->cleanValue($old->field_type ?? null);
        $oldIndustryType = $this->cleanValue($old->industry_type ?? null);

        // Both master maps use lowercase, trimmed keys.
        $fieldTypeKey = $oldFieldType !== null
            ? mb_strtolower(trim($oldFieldType))
            : null;

        $industryTypeKey = $oldIndustryType !== null
            ? mb_strtolower(trim($oldIndustryType))
            : null;

        /*
         * Map field_type.
         * If it contains an industry name instead, map it to industry_type_id.
         */
        if ($fieldTypeKey !== null) {
            if (isset($this->fieldTypeMap[$fieldTypeKey])) {
                $fieldTypeId = $this->fieldTypeMap[$fieldTypeKey];
            } elseif (isset($this->industryTypeMap[$fieldTypeKey])) {
                $industryTypeId = $this->industryTypeMap[$fieldTypeKey];
            } else {
                $this->unknownFieldTypes[$oldFieldType] = true;
            }
        }

        /*
         * Map industry_type.
         * If it contains a field type instead, map it to field_type_id.
         */
        if ($industryTypeKey !== null) {
            if (isset($this->industryTypeMap[$industryTypeKey])) {
                $industryTypeId = $this->industryTypeMap[$industryTypeKey];
            } elseif (isset($this->fieldTypeMap[$industryTypeKey])) {
                $fieldTypeId = $this->fieldTypeMap[$industryTypeKey];
            } else {
                $this->unknownIndustryTypes[$oldIndustryType] = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $data = [
            /*
            |--------------------------------------------------------------------------
            | Preserve Old ID
            |--------------------------------------------------------------------------
            */

            'id' => $old->id,

            /*
            |--------------------------------------------------------------------------
            | Existing Mappings
            |--------------------------------------------------------------------------
            */

            'learner_id' => $old->learner_id,
            'partner_id' => null,
            'partner_center_id' => null,
            'partner_placement_user_id' => null,
            'field_agent_id' => $old->ys_id,

            'review_status' => $reviewStatus,

            /*
            |--------------------------------------------------------------------------
            | New Workflow Status
            |--------------------------------------------------------------------------
            */

            'workflow_status' => 'draft',

            'beneficiary_phone_number' => $old->beneficiary_phone_number,
            'beneficiary_name' => $old->beneficiary_name,

            /*
            |--------------------------------------------------------------------------
            | Event Mapping
            |--------------------------------------------------------------------------
            */

            'event_master_id' => $old->event_id,
            'event_master_type_id' => $old->event_type,

            /*
            |--------------------------------------------------------------------------
            | No Old Source
            |--------------------------------------------------------------------------
            */

            'event_type_name' => null,

            /*
            |--------------------------------------------------------------------------
            | Existing Event Data
            |--------------------------------------------------------------------------
            */

            'event_category_name' => $old->event_category_name,

            'event_create_date' => $old->event_date_created,
            'event_date_submitted' => $old->event_date_submitted,
            'event_value' => $old->event_value,

            /*
            |--------------------------------------------------------------------------
            | Master Table IDs
            |--------------------------------------------------------------------------
            */

            'field_type_id' => $fieldTypeId,
            'industry_type_id' => $industryTypeId,

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            'uploaded_doc_1' => null,
            'uploaded_doc_2' => null,
            'uploaded_doc_3' => null,

            'last_comment' => $old->comment,
            'uploaded_doc_links' => $old->uploaded_doc_links,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $old->status,

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' => $old->created_at,
            'updated_at' => $old->updated_at,
        ];

        /*
        |--------------------------------------------------------------------------
        | Dry Run
        |--------------------------------------------------------------------------
        */

        if ($dryRun) {
            $this->migrated++;
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */

        DB::connection($this->newConnection)
            ->table('event_transactions')
            ->insert($data);

        $this->migrated++;
    }

    /*
    |--------------------------------------------------------------------------
    | Clean Value
    |--------------------------------------------------------------------------
    */

   private function cleanValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if (
            $value === '' ||
            strcasecmp($value, 'NULL') === 0
        ) {
            return null;
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    private function displaySummary(bool $dryRun): void
    {
        $this->info('==============================================');
        $this->info(' Migration Completed');
        $this->info('==============================================');

        $this->line("Total Old Records : {$this->total}");
        $this->line("Migrated          : {$this->migrated}");
        $this->line("Skipped           : {$this->skipped}");
        $this->line("Failed            : {$this->failed}");

        /*
        |--------------------------------------------------------------------------
        | Unknown Field Types
        |--------------------------------------------------------------------------
        */

        if (!empty($this->unknownFieldTypes)) {

            $this->newLine();

            $this->warn('Unknown Field Types Found:');

            foreach (array_keys($this->unknownFieldTypes) as $value) {
                $this->line("- {$value}");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Unknown Industry Types
        |--------------------------------------------------------------------------
        */

        if (!empty($this->unknownIndustryTypes)) {

            $this->newLine();

            $this->warn('Unknown Industry Types Found:');

            foreach (array_keys($this->unknownIndustryTypes) as $value) {
                $this->line("- {$value}");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Unknown Review Status
        |--------------------------------------------------------------------------
        */

        if (!empty($this->unknownReviewStatuses)) {

            $this->newLine();

            $this->warn('Unknown Review Statuses Found:');

            foreach (array_keys($this->unknownReviewStatuses) as $value) {
                $this->line("- {$value}");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Dry Run Message
        |--------------------------------------------------------------------------
        */

        if ($dryRun) {
            $this->newLine();

            $this->warn(
                'DRY RUN completed. No records were inserted.'
            );
        } else {

            $this->newLine();

            $this->info(
                'Migration completed successfully.'
            );
        }
    }
}
