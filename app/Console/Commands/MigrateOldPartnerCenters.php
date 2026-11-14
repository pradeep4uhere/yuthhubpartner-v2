<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldPartnerCenters extends Command
{
    protected $signature = 'partner-centers:migrate
                            {--dry-run : Show migration without changing data}
                            {--limit= : Migrate only limited records}';

    protected $description = 'Migrate partner centers from old database to new database while preserving original IDs';

    /**
     * Execute the Partner Center migration.
     */
    public function handle(): int
    {
        $this->info('Starting Partner Center Migration...');
        $this->newLine();

        $oldDb = DB::connection('old_mysql');
        $newDb = DB::connection('mysql');

        $limit = $this->option('limit');

        if ($limit !== null && (!is_numeric($limit) || (int) $limit < 1)) {
            $this->error('Limit must be a positive integer.');

            return self::FAILURE;
        }

        $query = $oldDb
            ->table('partner_centers')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $total = (clone $query)->count();

        $this->info("Old DB Records: {$total}");

        if ($total === 0) {
            $this->warn('No Partner Centers found.');

            return self::SUCCESS;
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $conflicts = 0;
        $failed = 0;

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No database changes will be made.');
            $this->newLine();
        }

        $query->chunkById(500, function ($oldCenters) use (
            $newDb,
            $dryRun,
            &$inserted,
            &$updated,
            &$skipped,
            &$conflicts,
            &$failed
        ) {
            foreach ($oldCenters as $oldCenter) {
                try {
                    $centerId = (int) $oldCenter->id;
                    $partnerId = trim((string) $oldCenter->partner_id);
                    $email = trim((string) ($oldCenter->email ?? ''));
                    $centerName = trim((string) ($oldCenter->center_name ?? ''));

                    /*
                     * Validate original Partner Center ID.
                     */
                    if ($centerId <= 0) {
                        $skipped++;
                        $this->warn('SKIPPED: Invalid Partner Center ID.');

                        continue;
                    }

                    /*
                     * Validate parent Partner ID.
                     */
                    if ($partnerId === '' || !ctype_digit($partnerId)) {
                        $skipped++;

                        $this->warn(
                            "SKIPPED: Invalid Partner ID for Center ID {$centerId}."
                        );

                        continue;
                    }

                    $partnerId = (int) $partnerId;

                    /*
                     * Validate mandatory Center Name.
                     */
                    if ($centerName === '') {
                        $skipped++;

                        $this->warn(
                            "SKIPPED: Center Name missing for ID {$centerId}."
                        );

                        continue;
                    }

                    /*
                     * Find the parent Partner using its preserved original ID.
                     */
                    $partner = $newDb
                        ->table('partners')
                        ->where('id', $partnerId)
                        ->first();

                    if (!$partner) {
                        $conflicts++;

                        $this->error(
                            "CONFLICT: Partner ID {$partnerId} missing for Center ID {$centerId}."
                        );

                        continue;
                    }

                    /*
                     * Get Partner Code from the new Partner record.
                     */
                    $partnerCode = trim((string) $partner->partner_code);

                    if ($partnerCode === '') {
                        $conflicts++;

                        $this->error(
                            "CONFLICT: Partner Code missing for Partner ID {$partnerId}."
                        );

                        continue;
                    }

                    /*
                     * Convert old numeric status into new status format.
                     */
                    $status = ((int) $oldCenter->status === 1)
                        ? 'active'
                        : 'inactive';

                    /*
                     * Prepare data according to the new table schema.
                     */
                    $data = [
                        'id' => $centerId,
                        'partner_id' => $partnerId,
                        'partner_code' => $partnerCode,
                        'name' => $centerName,
                        'contact_number' => $oldCenter->contact_number,
                        'email' => $email !== '' ? $email : null,
                        'status' => $status,
                        'password' => $oldCenter->password,
                        'remember_token' => null,
                        'created_at' => $oldCenter->created_at,
                        'updated_at' => $oldCenter->updated_at,
                    ];

                    /*
                     * Find existing record using original Center ID.
                     */
                    $existingById = $newDb
                        ->table('partner_centers')
                        ->where('id', $centerId)
                        ->first();

                    /*
                     * Check unique email conflicts.
                     */
                    $existingByEmail = null;

                    if ($email !== '') {
                        $existingByEmail = $newDb
                            ->table('partner_centers')
                            ->where('email', $email)
                            ->first();
                    }

                    /*
                     * Never overwrite another Center's ID or identity.
                     */
                    if (
                        $existingByEmail &&
                        (int) $existingByEmail->id !== $centerId
                    ) {
                        $conflicts++;

                        $this->error(
                            "CONFLICT: Email {$email} already belongs to Center ID {$existingByEmail->id}; old ID {$centerId}."
                        );

                        continue;
                    }

                    /*
                     * Do not silently change the parent of an existing Center.
                     */
                    if (
                        $existingById &&
                        (int) $existingById->partner_id !== $partnerId
                    ) {
                        $conflicts++;

                        $this->error(
                            "CONFLICT: Center ID {$centerId} has a different Partner ID in the new database."
                        );

                        continue;
                    }

                    /*
                     * Update existing Center without modifying its primary key.
                     */
                    if ($existingById) {
                        if ($dryRun) {
                            $updated++;

                            $this->line(
                                "UPDATE: Center ID {$centerId} - {$centerName}"
                            );

                            continue;
                        }

                        $updateData = $data;

                        unset($updateData['id']);

                        $newDb->table('partner_centers')
                            ->where('id', $centerId)
                            ->update($updateData);

                        $updated++;

                        $this->line(
                            "UPDATED: Center ID {$centerId} - {$centerName}"
                        );
                    }

                    /*
                     * Insert new Center while preserving its original ID.
                     */
                    else {
                        if ($dryRun) {
                            $inserted++;

                            $this->line(
                                "INSERT: Center ID {$centerId} - {$centerName}"
                            );

                            continue;
                        }

                        $newDb->table('partner_centers')
                            ->insert($data);

                        $inserted++;

                        $this->line(
                            "INSERTED: Center ID {$centerId} - {$centerName}"
                        );
                    }
                } catch (Throwable $e) {
                    $failed++;

                    $this->error(
                        "FAILED: Center ID {$oldCenter->id}"
                    );

                    $this->error($e->getMessage());
                }
            }
        }, 'id');

        $this->newLine();

        $this->info('====================================');
        $this->info('Partner Center Migration Completed');
        $this->info('====================================');

        $this->table(
            ['Type', 'Count'],
            [
                ['Total Old Records', $total],
                ['Inserted', $inserted],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Conflicts', $conflicts],
                ['Failed', $failed],
            ]
        );

        if ($dryRun) {
            $this->newLine();

            $this->warn(
                'DRY RUN completed. No database records were changed.'
            );
        }

        return ($failed > 0 || $conflicts > 0)
            ? self::FAILURE
            : self::SUCCESS;
    }
}
