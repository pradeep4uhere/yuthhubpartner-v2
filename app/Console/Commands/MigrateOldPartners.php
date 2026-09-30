<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldPartners extends Command
{
    protected $signature = 'partners:migrate
                            {--dry-run : Show migration without changing data}
                            {--limit= : Migrate only limited records}';

    protected $description = 'Migrate partners from old database to new database';

    public function handle(): int
    {
        $this->info('Starting Partner Migration...');
        $this->newLine();

        $oldDb = DB::connection('old_mysql');
        $newDb = DB::connection('mysql');

        $query = $oldDb
            ->table('partners')
            ->orderBy('id');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $total = $query->count();

        $this->info("Old DB Records: {$total}");

        if ($total === 0) {
            $this->warn('No partners found in old database.');

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

        $query->chunkById(500, function ($oldPartners) use (
            $newDb,
            $dryRun,
            &$inserted,
            &$updated,
            &$skipped,
            &$failed
        ) {

            foreach ($oldPartners as $oldPartner) {

                try {

                    /*
                     * partner_id in OLD DB becomes
                     * partner_code in NEW DB.
                     */
                    $partnerCode = trim((string) $oldPartner->partner_id);

                    if ($partnerCode === '') {

                        $this->warn(
                            "SKIPPED: Old ID {$oldPartner->id} has no partner_id"
                        );

                        $skipped++;

                        continue;
                    }

                    /*
                     * New status format:
                     *
                     * OLD:
                     * 1 = active
                     * 0 = inactive
                     *
                     * NEW:
                     * active / inactive
                     */
                    $status = ((int) $oldPartner->status === 1)
                        ? 'active'
                        : 'inactive';

                    /*
                     * Prepare data for NEW database.
                     */
                    $data = [
                        'id'           => $oldPartner->id,
                        'partner_code'   => $partnerCode,
                        'name'           => $oldPartner->name,
                        'contact_number' => $oldPartner->contact_number,
                        'email'          => $oldPartner->email,
                        'status'         => $status,
                        'password'       => $oldPartner->password,

                        'created_at'     => $oldPartner->created_at,
                        'updated_at'     => $oldPartner->updated_at,
                    ];

                    /*
                     * Find partner in NEW DB
                     * using partner_code.
                     */
                    $existing = $newDb
                        ->table('partners')
                        ->where('partner_code', $partnerCode)
                        ->first();

                    /*
                     * ==========================
                     * UPDATE
                     * ==========================
                     */
                    if ($existing) {

                        if ($dryRun) {

                            $this->line(
                                "UPDATE: {$partnerCode} - {$oldPartner->name}"
                            );

                            $updated++;

                            continue;
                        }

                        $newDb
                            ->table('partners')
                            ->where('id', $existing->id)
                            ->update($data);

                        $this->line(
                            "UPDATED: {$partnerCode} - {$oldPartner->name}"
                        );

                        $updated++;
                    }

                    /*
                     * ==========================
                     * INSERT
                     * ==========================
                     */
                    else {

                        if ($dryRun) {

                            $this->line(
                                "INSERT: {$partnerCode} - {$oldPartner->name}"
                            );

                            $inserted++;

                            continue;
                        }

                        $newDb
                            ->table('partners')
                            ->insert($data);

                        $this->line(
                            "INSERTED: {$partnerCode} - {$oldPartner->name}"
                        );

                        $inserted++;
                    }

                } catch (Throwable $e) {

                    $failed++;

                    $this->error(
                        "FAILED: Old ID {$oldPartner->id}, Partner Code: {$oldPartner->partner_id}"
                    );

                    $this->error(
                        $e->getMessage()
                    );
                }
            }
        });

        $this->newLine();

        $this->info('====================================');
        $this->info('Partner Migration Completed');
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
