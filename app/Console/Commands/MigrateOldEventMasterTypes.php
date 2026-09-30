<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldEventMasterTypes extends Command
{
    protected $signature = 'event-master-types:migrate
                            {--dry-run : Show migration without changing data}
                            {--limit= : Migrate only limited records}';

    protected $description = 'Migrate event_master_type from old database to new database while preserving IDs';

    public function handle(): int
    {
        $oldDb = DB::connection('old_mysql');
        $newDb = DB::connection('mysql');

        $this->info('Starting Event Master Type Migration...');

        $query = $oldDb
            ->table('yuwaah_event_type')
            ->orderBy('id');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $total = 0;
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $dryRun = $this->option('dry-run');

        $query->chunkById(500, function ($oldRecords) use (
            $newDb,
            &$total,
            &$inserted,
            &$updated,
            &$skipped,
            &$failed,
            $dryRun
        ) {
            foreach ($oldRecords as $oldRecord) {

                $total++;

                try {

                    $data = [
                        // IMPORTANT: Preserve old ID
                        'id' => $oldRecord->id,

                        'name' => $oldRecord->name,
                        'description' => $oldRecord->description,
                        'status' => $oldRecord->status,

                        'created_at' => $oldRecord->created_at,
                        'updated_at' => $oldRecord->updated_at,
                    ];

                    /*
                     * Check existing record by SAME ID
                     */
                    $existing = $newDb
                        ->table('event_master_type')
                        ->where('id', $oldRecord->id)
                        ->first();

                    if ($existing) {

                        if ($dryRun) {
                            $this->line(
                                "WOULD UPDATE: ID {$oldRecord->id} - {$oldRecord->name}"
                            );
                        } else {
                            $newDb
                                ->table('event_master_type')
                                ->where('id', $oldRecord->id)
                                ->update([
                                    'name' => $data['name'],
                                    'description' => $data['description'],
                                    'status' => $data['status'],
                                    'created_at' => $data['created_at'],
                                    'updated_at' => $data['updated_at'],
                                ]);

                            $this->line(
                                "UPDATED: ID {$oldRecord->id} - {$oldRecord->name}"
                            );
                        }

                        $updated++;

                    } else {

                        if ($dryRun) {

                            $this->line(
                                "WOULD INSERT: ID {$oldRecord->id} - {$oldRecord->name}"
                            );

                        } else {

                            $newDb
                                ->table('event_master_type')
                                ->insert($data);

                            $this->line(
                                "INSERTED: ID {$oldRecord->id} - {$oldRecord->name}"
                            );
                        }

                        $inserted++;
                    }

                } catch (Throwable $e) {

                    $failed++;

                    $this->error(
                        "FAILED: ID {$oldRecord->id} - {$e->getMessage()}"
                    );
                }
            }
        });

        $this->newLine();

        $this->info('========================================');
        $this->info('Event Master Type Migration Summary');
        $this->info('========================================');

        $this->info("Total Records : {$total}");
        $this->info("Inserted      : {$inserted}");
        $this->info("Updated       : {$updated}");
        $this->info("Skipped       : {$skipped}");
        $this->info("Failed        : {$failed}");

        if ($dryRun) {
            $this->warn('DRY RUN - No data was changed.');
        } else {
            $this->info('Migration completed.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
