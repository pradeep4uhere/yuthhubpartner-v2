<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldFieldAgents extends Command
{
    protected $signature = 'field-agents:migrate
                            {--dry-run : Show what will happen without modifying the database}
                            {--limit= : Migrate only a limited number of records}';

    protected $description = 'Migrate field agents from old database to new database';

    public function handle(): int
    {
        $this->info('Starting Field Agent Migration...');
        $this->newLine();

        $oldDb = DB::connection('old_mysql');
        $newDb = DB::connection('mysql');

        $query = $oldDb
            ->table('yuwaah_sakhi')
            ->orderBy('id');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $total = $query->count();

        $this->info("Old DB Records: {$total}");

        if ($total === 0) {
            $this->warn('No records found in old database.');
            return self::SUCCESS;
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No database changes will be made.');
            $this->newLine();
        }

        $query->chunkById(500, function ($agents) use (
            $newDb,
            $dryRun,
            &$inserted,
            &$updated,
            &$skipped,
            &$failed
        ) {

            foreach ($agents as $oldAgent) {

                try {

                    /*
                     * sakhi_id is our business matching key.
                     */
                    if (empty($oldAgent->sakhi_id)) {
                        $this->warn(
                            "SKIPPED: Old ID {$oldAgent->id} has no sakhi_id"
                        );

                        $skipped++;
                        continue;
                    }

                    /*
                     * Convert old status:
                     *
                     * 1 => active
                     * 0 => inactive
                     */
                    $status = ((int) $oldAgent->status === 1)
                        ? 'active'
                        : 'inactive';

                    /*
                     * Map old database fields
                     * to new database fields.
                     */
                    $data = [
                        'id'   => $oldAgent->id,
                        'name' => $oldAgent->name,
                        'email' => $oldAgent->email,
                        'contact_number' => $oldAgent->contact_number,
                        'csc_id' => $oldAgent->csc_id,
                        'sakhi_id' => $oldAgent->sakhi_id,

                        'partner_id' => $oldAgent->partner_id,
                        'partner_center_id' => $oldAgent->partner_center_id,

                        // OLD:
                        // partner_placement_user_id
                        //
                        // NEW:
                        // placement_partner_user_id

                        'placement_partner_user_id' => !empty($oldAgent->partner_placement_user_id)
                            ? $oldAgent->partner_placement_user_id
                            : NULL,

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

                    /*
                     * Check if record already exists
                     * in NEW database.
                     */
                    $existing = $newDb
                        ->table('field_agents')
                        ->where('sakhi_id', $oldAgent->sakhi_id)
                        ->first();

                    if ($existing) {

                        if ($dryRun) {

                            $this->line(
                                "UPDATE: {$oldAgent->sakhi_id}"
                            );

                            $updated++;
                            continue;
                        }

                        $newDb
                            ->table('field_agents')
                            ->where('id', $existing->id)
                            ->update($data);

                        $this->line(
                            "UPDATED: {$oldAgent->sakhi_id}"
                        );

                        $updated++;

                    } else {

                        if ($dryRun) {

                            $this->line(
                                "INSERT: {$oldAgent->sakhi_id}"
                            );

                            $inserted++;
                            continue;
                        }

                        $newDb
                            ->table('field_agents')
                            ->insert($data);

                        $this->line(
                            "INSERTED: {$oldAgent->sakhi_id}"
                        );

                        $inserted++;
                    }

                } catch (Throwable $e) {

                    $failed++;

                    $this->error(
                        "FAILED: Old ID {$oldAgent->id}, Sakhi ID {$oldAgent->sakhi_id}"
                    );

                    $this->error(
                        $e->getMessage()
                    );
                }
            }
        });

        $this->newLine();
        $this->info('====================================');
        $this->info('Field Agent Migration Completed');
        $this->info('====================================');

        $this->table(
            [
                'Type',
                'Count',
            ],
            [
                ['Total Old Records', $total],
                ['Inserted', $inserted],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn(
                'DRY RUN completed. No database records were changed.'
            );
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
