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
    private array $fieldAgentMap = [];

    private int $total = 0;
    private int $migrated = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $failed = 0;

    /**
     * Execute the old event transaction migration command.
     */
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
            $this->warn('DRY RUN MODE - No database changes will be made.');
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
            $this->info('Old database connection successful.');

            DB::connection($this->newConnection)->getPdo();
            $this->info('New database connection successful.');
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

        try {
            $this->loadMasterData();
        } catch (Throwable $e) {
            $this->error('Failed to load master data: ' . $e->getMessage());
            return self::FAILURE;
        }

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

    /**
     * Load field types, industry types, and field agent relationship mappings.
     */
    private function loadMasterData(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Load Field Types
        |--------------------------------------------------------------------------
        */

        $this->info('Loading field_types...');

        $this->fieldTypeMap = [];

        $fieldTypes = DB::connection($this->newConnection)
            ->table('field_types')
            ->get(['id', 'name']);

        foreach ($fieldTypes as $fieldType) {
            $key = mb_strtolower(trim((string) $fieldType->name));

            if ($key !== '') {
                $this->fieldTypeMap[$key] = (int) $fieldType->id;
            }
        }

        $this->info('Field Types Loaded: ' . count($this->fieldTypeMap));

        /*
        |--------------------------------------------------------------------------
        | Load Industry Types
        |--------------------------------------------------------------------------
        */

        $this->info('Loading industry_types...');

        $this->industryTypeMap = [];

        $industryTypes = DB::connection($this->newConnection)
            ->table('industry_types')
            ->get(['id', 'name']);

        foreach ($industryTypes as $industryType) {
            $key = mb_strtolower(trim((string) $industryType->name));

            if ($key !== '') {
                $this->industryTypeMap[$key] = (int) $industryType->id;
            }
        }

        $this->info('Industry Types Loaded: ' . count($this->industryTypeMap));

        /*
        |--------------------------------------------------------------------------
        | Load Field Agent Mappings
        |--------------------------------------------------------------------------
        */

        $this->info('Loading field_agents...');

        $this->fieldAgentMap = [];

        $fieldAgents = DB::connection($this->newConnection)
            ->table('field_agents')
            ->get([
                'id',
                'partner_id',
                'partner_center_id',
                'placement_partner_user_id',
            ]);

        foreach ($fieldAgents as $agent) {
            $this->fieldAgentMap[(int) $agent->id] = [
                'partner_id' => $agent->partner_id,
                'partner_center_id' => $agent->partner_center_id,
                'partner_placement_user_id' => $agent->placement_partner_user_id,
            ];
        }

        $this->info('Field Agents Loaded: ' . count($this->fieldAgentMap));
    }

    /**
     * Insert a new event transaction or update an existing transaction by its old ID.
     */
    private function migrateRecord(object $old, bool $dryRun): void
    {
        /*
        |--------------------------------------------------------------------------
        | Review Status
        |--------------------------------------------------------------------------
        */

        $reviewStatus = null;

        if ($old->review_status !== null) {
            $oldReviewStatus = trim((string) $old->review_status);

            if (array_key_exists($oldReviewStatus, $this->reviewStatusMap)) {
                $reviewStatus = $this->reviewStatusMap[$oldReviewStatus];
            } else {
                $this->unknownReviewStatuses[$oldReviewStatus] = true;
                $reviewStatus = $oldReviewStatus;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Field Type / Industry Type Mapping
        |--------------------------------------------------------------------------
        */

        $fieldTypeId = null;
        $industryTypeId = null;

        $oldFieldType = $this->cleanValue($old->field_type ?? null);
        $oldIndustryType = $this->cleanValue($old->industry_type ?? null);

        $fieldTypeKey = $oldFieldType !== null
            ? mb_strtolower(trim($oldFieldType))
            : null;

        $industryTypeKey = $oldIndustryType !== null
            ? mb_strtolower(trim($oldIndustryType))
            : null;

        // First match field_type against field_types.
        if ($fieldTypeKey !== null) {
            if (isset($this->fieldTypeMap[$fieldTypeKey])) {
                $fieldTypeId = $this->fieldTypeMap[$fieldTypeKey];
            } elseif (isset($this->industryTypeMap[$fieldTypeKey])) {
                $industryTypeId = $this->industryTypeMap[$fieldTypeKey];
            } else {
                $this->unknownFieldTypes[$oldFieldType] = true;
            }
        }

        // First match industry_type against industry_types.
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
        | Resolve Field Agent Relationships
        |--------------------------------------------------------------------------
        */

        $oldFieldAgentId = (int) ($old->ys_id ?? 0);

        $fieldAgent = $oldFieldAgentId > 0
            ? ($this->fieldAgentMap[$oldFieldAgentId] ?? null)
            : null;

        /*
        |--------------------------------------------------------------------------
        | Prepare Transaction Data
        |--------------------------------------------------------------------------
        */

        $data = [
            'learner_id' => $old->learner_id,

            'partner_id' => $fieldAgent['partner_id'] ?? null,
            'partner_center_id' => $fieldAgent['partner_center_id'] ?? null,
            'partner_placement_user_id' => $fieldAgent['partner_placement_user_id'] ?? null,
            'field_agent_id' => $fieldAgent !== null ? $oldFieldAgentId : null,

            'review_status' => $reviewStatus,
            'workflow_status' => 'submitted',

            'beneficiary_phone_number' => $old->beneficiary_phone_number,
            'beneficiary_name' => $old->beneficiary_name,

            'event_master_id' => $old->event_id,
            'event_master_type_id' => $old->event_type,
            'event_type_name' => $this->cleanValue($old->event_name),

            'event_category_name' => $this->cleanValue($old->event_category_name)
                ?? $this->cleanValue($old->event_category),

            'event_create_date' => $old->event_date_created,
            'event_date_submitted' => $old->event_date_submitted,
            'event_value' => $old->event_value,

            'field_type_id' => $fieldTypeId,
            'industry_type_id' => $industryTypeId,

            'uploaded_doc_1' => null,
            'uploaded_doc_2' => null,
            'uploaded_doc_3' => null,

            'last_comment' => $old->comment,
            'uploaded_doc_links' => $old->uploaded_doc_links,
            'status' => $old->status,

            'created_at' => $old->created_at,
            'updated_at' => $old->updated_at,
        ];

        /*
        |--------------------------------------------------------------------------
        | Check Existing Transaction
        |--------------------------------------------------------------------------
        */

        $exists = DB::connection($this->newConnection)
            ->table('event_transactions')
            ->where('id', $old->id)
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Dry Run
        |--------------------------------------------------------------------------
        */

        if ($dryRun) {
            if ($exists) {
                $this->updated++;
            } else {
                $this->migrated++;
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Insert or Update
        |--------------------------------------------------------------------------
        */

        if ($exists) {
            DB::connection($this->newConnection)
                ->table('event_transactions')
                ->where('id', $old->id)
                ->update($data);

            $this->updated++;
        } else {
            $data['id'] = $old->id;

            DB::connection($this->newConnection)
                ->table('event_transactions')
                ->insert($data);

            $this->migrated++;
        }
    }

    /**
     * Normalize source values and convert empty or literal NULL strings to null.
     */
    private function cleanValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        return $value;
    }

    /**
     * Display migration counters and any source values that could not be mapped.
     */
    private function displaySummary(bool $dryRun): void
    {
        $this->info('==============================================');
        $this->info(' Migration Completed');
        $this->info('==============================================');

        $this->line("Total Old Records : {$this->total}");
        $this->line("Inserted          : {$this->migrated}");
        $this->line("Updated           : {$this->updated}");
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
        | Unknown Review Statuses
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
        | Completion Message
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN completed. No database changes were made.');
        } else {
            $this->info('Migration completed successfully.');
        }
    }
}
