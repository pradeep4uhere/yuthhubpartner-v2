<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateOldFieldAgents extends Command
{
    protected $signature = 'field-agents:migrate
                            {--dry-run : Show what will happen without modifying the database}
                            {--limit= : Migrate only a limited number of records}';

    protected $description = 'Safely migrate Field Agents from old database to new database';

    private string $oldConnection = 'old_mysql';

    private string $newConnection = 'mysql';

    /**
     * Run the Field Agent migration with duplicate Sakhi ID and transaction verification.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');

        if ($limit !== null && (!is_numeric($limit) || (int) $limit <= 0)) {
            $this->error('Limit must be a positive number.');
            return self::FAILURE;
        }

        $oldDb = DB::connection($this->oldConnection);
        $newDb = DB::connection($this->newConnection);

        $this->info('Starting Field Agent Migration...');
        $this->newLine();

        // Verify both database connections before processing records.
        try {
            $oldDb->getPdo();
            $newDb->getPdo();

            $this->info('Old database connection successful.');
            $this->info('New database connection successful.');
        } catch (Throwable $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Find duplicate actual CSC IDs using sakhi_id, not csc_id.
        $duplicateSakhiQuery = $oldDb->table('yuwaah_sakhi')
            ->selectRaw('LOWER(TRIM(sakhi_id)) AS normalized_sakhi_id')
            ->whereNotNull('sakhi_id')
            ->whereRaw("TRIM(sakhi_id) != ''")
            ->groupByRaw('LOWER(TRIM(sakhi_id))')
            ->havingRaw('COUNT(*) > 1');

        // Prepare source records and identify duplicate actual CSC IDs.
        $query = $oldDb->table('yuwaah_sakhi as old')
            ->leftJoinSub(
                $duplicateSakhiQuery,
                'duplicate_sakhi',
                function ($join) {
                    $join->on(
                        DB::raw('LOWER(TRIM(old.sakhi_id))'),
                        '=',
                        'duplicate_sakhi.normalized_sakhi_id'
                    );
                }
            )
            ->select('old.*')
            ->selectRaw(
                'CASE WHEN duplicate_sakhi.normalized_sakhi_id IS NOT NULL THEN 1 ELSE 0 END AS is_duplicate_sakhi'
            )
            ->orderBy('old.id');

        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $total = (clone $query)->count();

        $this->info("Old DB Records: {$total}");

        if ($total === 0) {
            $this->warn('No records found.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No database changes will be made.');
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $duplicateSkipped = 0;
        $existingDuplicateSkipped = 0;
        $conflicts = 0;
        $sakhiConflicts = 0;
        $failed = 0;
        $emailAdjusted = 0;
        $processed = 0;

        // Process old Field Agents in batches to control memory usage.
        $query->chunkById(500, function ($agents) use (
            $oldDb,
            $newDb,
            $dryRun,
            &$inserted,
            &$updated,
            &$skipped,
            &$duplicateSkipped,
            &$existingDuplicateSkipped,
            &$conflicts,
            &$sakhiConflicts,
            &$failed,
            &$emailAdjusted,
            &$processed,
            $total
        ) {
            foreach ($agents as $oldAgent) {
                $processed++;

                try {
                    // Validate the actual CSC ID before migration.
                    $sakhiId = trim((string) ($oldAgent->sakhi_id ?? ''));

                    if ($sakhiId === '') {
                        $this->warn(
                            "SKIPPED: Old ID {$oldAgent->id} has no Sakhi ID."
                        );

                        $skipped++;
                        continue;
                    }

                    // Check whether the source record belongs to a duplicate actual CSC group.
                    $isDuplicateSakhi = (bool) $oldAgent->is_duplicate_sakhi;

                    // Find an existing target record using its original primary key.
                    $existingById = $newDb->table('field_agents')
                        ->where('id', $oldAgent->id)
                        ->first();

                    // Keep an already migrated duplicate unchanged only when its Sakhi ID matches.
                    if (
                        $isDuplicateSakhi &&
                        $existingById &&
                        (string) $existingById->sakhi_id === $sakhiId
                    ) {
                        $existingDuplicateSkipped++;

                        $this->line(
                            "KEEP EXISTING: ID {$oldAgent->id} | {$sakhiId}"
                        );

                        continue;
                    }

                    // Skip a missing duplicate actual CSC record when it has no old transactions.
                    if ($isDuplicateSakhi) {
                        $hasTransactions = $oldDb->table('event_transactions')
                            ->where('ys_id', $oldAgent->id)
                            ->exists();

                        if (!$hasTransactions) {
                            $duplicateSkipped++;

                            $this->line(
                                "DUPLICATE SKIPPED: ID {$oldAgent->id} | "
                                . "{$sakhiId} | No Event Transactions"
                            );

                            continue;
                        }
                    }

                    // Convert old numeric status into the new status format.
                    $status = ((int) $oldAgent->status === 1)
                        ? 'active'
                        : 'inactive';

                    // Prepare the source email and trim whitespace.
                    $originalEmail = trim((string) ($oldAgent->email ?? ''));

                    // Prepare the new Field Agent data while preserving the old primary key.
                    $data = [
                        'id' => $oldAgent->id,
                        'name' => $oldAgent->name,
                        'email' => $originalEmail,
                        'contact_number' => $oldAgent->contact_number,
                        'csc_id' => $oldAgent->csc_id,
                        'sakhi_id' => $sakhiId,

                        'partner_id' => $oldAgent->partner_id,
                        'partner_center_id' => $oldAgent->partner_center_id,

                        'placement_partner_user_id' =>
                            !empty($oldAgent->partner_placement_user_id)
                                ? $oldAgent->partner_placement_user_id
                                : null,

                        'state' => !empty($oldAgent->state)
                            ? trim($oldAgent->state)
                            : 'Unknown',

                        'district' => !empty($oldAgent->district)
                            ? trim($oldAgent->district)
                            : 'Unknown',

                        'status' => $status,
                        'password' => $oldAgent->password,
                        'remember_token' => $oldAgent->remember_token,
                        'created_at' => $oldAgent->created_at,
                        'updated_at' => $oldAgent->updated_at,
                    ];

                    // Verify primary key, Sakhi ID and email uniqueness inside one transaction.
                    $result = $newDb->transaction(function () use (
                        $newDb,
                        $oldAgent,
                        $sakhiId,
                        $originalEmail,
                        &$data,
                        $dryRun
                    ) {
                        // Find an existing record using the original primary key.
                        $existingById = $newDb->table('field_agents')
                            ->where('id', $oldAgent->id)
                            ->first();

                        // Find an existing record using the actual CSC ID.
                        $existingBySakhi = $newDb->table('field_agents')
                            ->whereRaw('LOWER(TRIM(sakhi_id)) = ?', [
                                strtolower($sakhiId)
                            ])
                            ->first();

                        // Never overwrite a primary key belonging to another Sakhi.
                        if (
                            $existingById &&
                            (string) $existingById->sakhi_id !== $sakhiId
                        ) {
                            return [
                                'status' => 'id_conflict',
                                'email_adjusted' => false,
                                'email' => null,
                            ];
                        }

                        // Never map an old ID to another primary key.
                        if (
                            !$existingById &&
                            $existingBySakhi &&
                            (int) $existingBySakhi->id !== (int) $oldAgent->id
                        ) {
                            return [
                                'status' => 'sakhi_conflict',
                                'email_adjusted' => false,
                                'email' => null,
                            ];
                        }

                        // Check whether the original email is blank or assigned to another agent.
                        $emailExists = $originalEmail !== ''
                            ? $newDb->table('field_agents')
                                ->where('email', $originalEmail)
                                ->where('id', '!=', $oldAgent->id)
                                ->exists()
                            : false;

                        $adjusted = false;

                        // Generate a deterministic placeholder for blank or duplicate emails.
                        if ($originalEmail === '' || $emailExists) {
                            $adjusted = true;

                            $hash = substr(
                                hash('sha256', $sakhiId),
                                0,
                                12
                            );

                            $baseEmail = 'field-agent-'
                                . $oldAgent->id
                                . '-'
                                . $hash
                                . '@migration.local';

                            $candidateEmail = $baseEmail;
                            $suffix = 1;

                            // Ensure the generated placeholder is unique in the target database.
                            while (
                                $newDb->table('field_agents')
                                    ->where('email', $candidateEmail)
                                    ->where('id', '!=', $oldAgent->id)
                                    ->exists()
                            ) {
                                $candidateEmail = 'field-agent-'
                                    . $oldAgent->id
                                    . '-'
                                    . $hash
                                    . '-'
                                    . $suffix
                                    . '@migration.local';

                                $suffix++;
                            }

                            $data['email'] = $candidateEmail;
                        }

                        // Update an existing record only when its primary key and Sakhi ID match.
                        if ($existingById) {
                            if (!$dryRun) {
                                $updateData = $data;

                                // Keep the existing primary key unchanged.
                                unset($updateData['id']);

                                $newDb->table('field_agents')
                                    ->where('id', $oldAgent->id)
                                    ->update($updateData);
                            }

                            return [
                                'status' => 'updated',
                                'email_adjusted' => $adjusted,
                                'email' => $data['email'],
                            ];
                        }

                        // Insert a new record while preserving its original ID.
                        if (!$dryRun) {
                            $newDb->table('field_agents')->insert($data);
                        }

                        return [
                            'status' => 'inserted',
                            'email_adjusted' => $adjusted,
                            'email' => $data['email'],
                        ];
                    }, 3);

                    // Count email adjustments independently of insert and update operations.
                    if ($result['email_adjusted']) {
                        $emailAdjusted++;

                        $this->line(
                            "EMAIL ADJUSTED: ID {$oldAgent->id} | "
                            . "{$originalEmail} => {$result['email']}"
                        );
                    }

                    // Count successful inserts and updates and identify conflicts.
                    if ($result['status'] === 'inserted') {
                        $inserted++;

                        $this->line(
                            "INSERTED: ID {$oldAgent->id} | {$sakhiId}"
                        );

                    } elseif ($result['status'] === 'updated') {
                        $updated++;

                        $this->line(
                            "UPDATED: ID {$oldAgent->id} | {$sakhiId}"
                        );

                    } elseif ($result['status'] === 'id_conflict') {
                        $conflicts++;

                        $this->error(
                            "ID CONFLICT: Old ID {$oldAgent->id}, Sakhi ID {$sakhiId}."
                        );

                    } elseif ($result['status'] === 'sakhi_conflict') {
                        $conflicts++;
                        $sakhiConflicts++;

                        $this->error(
                            "SAKHI ID CONFLICT: {$sakhiId} exists with a different Field Agent ID."
                        );
                    }

                } catch (Throwable $e) {
                    $failed++;

                    // Store complete failure details in the Laravel log for investigation.
                    Log::error('Field Agent migration failed', [
                        'old_id' => $oldAgent->id,
                        'sakhi_id' => $oldAgent->sakhi_id ?? null,
                        'csc_id' => $oldAgent->csc_id ?? null,
                        'email' => $oldAgent->email ?? null,
                        'partner_id' => $oldAgent->partner_id ?? null,
                        'partner_center_id' => $oldAgent->partner_center_id ?? null,
                        'error' => $e->getMessage(),
                    ]);

                    $this->error(
                        "FAILED: Old ID {$oldAgent->id}, Sakhi ID "
                        . ($oldAgent->sakhi_id ?? 'NULL')
                    );

                    $this->error($e->getMessage());
                }
            }

            // Display migration progress after each completed batch.
            $this->output->write("\rProgress: {$processed}/{$total}");
        }, 'old.id', 'id');

        $this->newLine(2);

        // Display final migration statistics.
        $this->info('====================================');
        $this->info('Field Agent Migration Completed');
        $this->info('====================================');

        $this->table(
            ['Type', 'Count'],
            [
                ['Total Old Records', $total],
                ['Inserted', $inserted],
                ['Updated', $updated],
                ['Duplicate Sakhi ID Skipped (No Transactions)', $duplicateSkipped],
                ['Existing Duplicate Kept Unchanged', $existingDuplicateSkipped],
                ['Other Skipped', $skipped],
                ['Email Adjusted', $emailAdjusted],
                ['Sakhi ID Conflicts', $sakhiConflicts],
                ['Total Conflicts', $conflicts],
                ['Failed', $failed],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN completed. No database records were changed.');
        }

        // Return failure when any record failed or requires manual reconciliation.
        return ($failed > 0 || $conflicts > 0)
            ? self::FAILURE
            : self::SUCCESS;
    }
}
