<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldPlacementUsers extends Command
{
    protected $signature = 'placement-users:migrate
                            {--dry-run : Only show what will be migrated}
                            {--limit= : Limit number of records}';

    protected $description = 'Migrate partner placement users from old database to new database';

    public function handle(): int
    {
        $this->info('Starting Partner Placement Users Migration...');

        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $query = DB::connection('old_mysql')
            ->table('partner_placement_users')
            ->orderBy('id');

        if ($limit) {
            $query->limit((int) $limit);
        }

        $oldUsers = $query->get();

        if ($oldUsers->isEmpty()) {
            $this->warn('No records found in old partner_placement_users table.');
            return self::SUCCESS;
        }

        $this->info("Found {$oldUsers->count()} old placement users.");

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($oldUsers as $oldUser) {

            try {

                /*
                 * pp_code is the business key.
                 * New table has UNIQUE constraint on pp_code.
                 */
                $ppCode = trim((string) $oldUser->pp_code);

                if ($ppCode === '') {

                    $this->warn(
                        "SKIPPED | Old ID: {$oldUser->id} | PP Code missing | {$oldUser->name}"
                    );

                    $skipped++;
                    continue;
                }

                /*
                 * New table has UNIQUE email.
                 */
                $email = trim((string) $oldUser->email);

                if ($email === '') {

                    $this->warn(
                        "SKIPPED | Old ID: {$oldUser->id} | Email missing | {$oldUser->name}"
                    );

                    $skipped++;
                    continue;
                }

                /*
                 * Check existing record by PP Code.
                 */
                $existing = DB::connection('mysql')
                    ->table('partner_placement_users')
                    ->where('pp_code', $ppCode)
                    ->first();

                /*
                 * New field is required but old table doesn't have it.
                 * For now use old user's name.
                 */
                $data = [
                    'name' => $oldUser->name,

                    'email' => $email,

                    'partner_placement_name' => $oldUser->name,

                    'contact_number' => $oldUser->phone,

                    'pp_code' => $ppCode,

                    'status' => ((int) $oldUser->status === 1)
                        ? 'active'
                        : 'inactive',

                    'password' => $oldUser->password,

                    'remember_token' => $oldUser->remember_token,

                    'created_at' => $oldUser->created_at,

                    'updated_at' => $oldUser->updated_at,
                ];

                if ($isDryRun) {

                    if ($existing) {
                        $this->line(
                            "WOULD UPDATE | Old ID: {$oldUser->id} | PP Code: {$ppCode}"
                        );
                    } else {
                        $this->line(
                            "WOULD INSERT | Old ID: {$oldUser->id} | PP Code: {$ppCode}"
                        );
                    }

                    continue;
                }

                /*
                 * Update existing record.
                 */
                if ($existing) {

                    DB::connection('mysql')
                        ->table('partner_placement_users')
                        ->where('id', $existing->id)
                        ->update($data);

                    $this->line(
                        "UPDATED | Old ID: {$oldUser->id} → New ID: {$existing->id} | PP Code: {$ppCode}"
                    );

                    $updated++;

                } else {

                    /*
                     * Insert new record.
                     *
                     * Do NOT insert old ID.
                     * New database will generate its own ID.
                     */
                    $newId = DB::connection('mysql')
                        ->table('partner_placement_users')
                        ->insertGetId($data);

                    $this->line(
                        "INSERTED | Old ID: {$oldUser->id} → New ID: {$newId} | PP Code: {$ppCode}"
                    );

                    $inserted++;
                }

            } catch (Throwable $e) {

                $failed++;

                $this->error(
                    "FAILED | Old ID: {$oldUser->id} | {$oldUser->name} | {$e->getMessage()}"
                );

                \Log::error('Placement User Migration Failed', [
                    'old_id' => $oldUser->id,
                    'name' => $oldUser->name,
                    'pp_code' => $oldUser->pp_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('==========================================');
        $this->info('Partner Placement Users Migration Summary');
        $this->info('==========================================');

        if ($isDryRun) {
            $this->info('DRY RUN: No database changes were made.');
        } else {
            $this->info("Inserted : {$inserted}");
            $this->info("Updated  : {$updated}");
        }

        $this->info("Skipped  : {$skipped}");
        $this->info("Failed   : {$failed}");

        $this->info('==========================================');

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}