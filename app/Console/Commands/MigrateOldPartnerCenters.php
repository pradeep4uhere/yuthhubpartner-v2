<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateOldPartnerCenters extends Command
{
    protected $signature = 'partner-centers:migrate
                            {--dry-run : Show what will happen without modifying the database}
                            {--limit= : Migrate only a limited number of records}';

    protected $description = 'Migrate Partner Centers from old database to new database';

    public function handle(): int
    {
        $this->info('Starting Partner Center Migration...');
        $this->newLine();

        $oldDb = DB::connection('old_mysql');
        $newDb = DB::connection('mysql');

        /*
        |--------------------------------------------------------------------------
        | Options
        |--------------------------------------------------------------------------
        */

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');

        if ($limit !== null && (!is_numeric($limit) || (int) $limit <= 0)) {
            $this->error('--limit must be a positive number.');

            return self::FAILURE;
        }

        $limit = $limit !== null ? (int) $limit : null;

        /*
        |--------------------------------------------------------------------------
        | Get old records count
        |--------------------------------------------------------------------------
        */

        $totalOldRecords = $oldDb
            ->table('partner_centers')
            ->count();

        if ($limit !== null) {
            $totalToProcess = min($totalOldRecords, $limit);
        } else {
            $totalToProcess = $totalOldRecords;
        }

        $this->info("Old DB Records: {$totalOldRecords}");
        $this->info("Records To Process: {$totalToProcess}");

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No database changes will be made.');
        }

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $processed = 0;

        /*
        |--------------------------------------------------------------------------
        | Process old partner centers
        |--------------------------------------------------------------------------
        */

        $query = $oldDb
            ->table('partner_centers')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->chunkById(500, function ($oldCenters) use (
            $oldDb,
            $newDb,
            $dryRun,
            &$inserted,
            &$updated,
            &$skipped,
            &$failed,
            &$processed,
            $totalToProcess
        ) {
            foreach ($oldCenters as $oldCenter) {

                $processed++;

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | 1. Validate old partner ID
                    |--------------------------------------------------------------------------
                    */

                    if (empty($oldCenter->partner_id)) {

                        $this->warn(
                            "SKIPPED: Old Partner ID is empty - " .
                            "Center: {$oldCenter->center_name}"
                        );

                        $skipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 2. Resolve OLD partner
                    |
                    | old partner_centers.partner_id
                    |             ↓
                    | old partners.id
                    |--------------------------------------------------------------------------
                    */

                    $oldPartner = $oldDb
                        ->table('partners')
                        ->where('id', $oldCenter->partner_id)
                        ->first();

                    if (!$oldPartner) {

                        $this->warn(
                            "SKIPPED: Old Partner not found - " .
                            "Old Partner ID: {$oldCenter->partner_id}, " .
                            "Center: {$oldCenter->center_name}"
                        );

                        $skipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 3. Get OLD business Partner Code
                    |
                    | old partners.partner_id
                    |--------------------------------------------------------------------------
                    */

                    $partnerCode = trim((string) $oldPartner->partner_id);

                    if ($partnerCode === '') {

                        $this->warn(
                            "SKIPPED: Old Partner has no Partner Code - " .
                            "Old Partner ID: {$oldPartner->id}, " .
                            "Partner Name: {$oldPartner->name}, " .
                            "Center: {$oldCenter->center_name}"
                        );

                        $skipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 4. Resolve NEW partner using business key
                    |
                    | OLD partner_code
                    |        ↓
                    | NEW partners.partner_code
                    |        ↓
                    | NEW partners.id
                    |--------------------------------------------------------------------------
                    */

                    $newPartner = $newDb
                        ->table('partners')
                        ->where('partner_code', $partnerCode)
                        ->first();

                    if (!$newPartner) {

                        $this->warn(
                            "SKIPPED: Partner not found in NEW DB - " .
                            "Partner Code: {$partnerCode}, " .
                            "Old Partner ID: {$oldPartner->id}, " .
                            "Center: {$oldCenter->center_name}"
                        );

                        $skipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 5. Convert status
                    |--------------------------------------------------------------------------
                    */

                    $status = ((int) $oldCenter->status === 1)
                        ? 'active'
                        : 'inactive';

                    /*
                    |--------------------------------------------------------------------------
                    | 6. Prepare data
                    |--------------------------------------------------------------------------
                    */

                    $data = [
                        'id' => $oldCenter->id,

                        'partner_id' => $newPartner->id,

                        // Business partner code
                        'partner_code' => $partnerCode,

                        'name' => $oldCenter->center_name,

                        'contact_number' => $oldCenter->contact_number,

                        'email' => $oldCenter->email,

                        'status' => $status,

                        'password' => $oldCenter->password,

                        'remember_token' => null,

                        'created_at' => $oldCenter->created_at,

                        'updated_at' => $oldCenter->updated_at,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | 7. Find existing center
                    |
                    | Priority:
                    |
                    | A. partner_centers_id if available
                    | B. email
                    | C. partner_id + name
                    |--------------------------------------------------------------------------
                    */

                    $existingCenter = null;

                    /*
                    |--------------------------------------------------------------------------
                    | A. Match using old partner_centers_id
                    |--------------------------------------------------------------------------
                    |
                    | NOTE:
                    | New table currently does NOT contain partner_centers_id.
                    | Therefore this can only be used if you add that column.
                    |
                    | For now we skip this and use email / partner + name.
                    |--------------------------------------------------------------------------
                    */

                    /*
                    |--------------------------------------------------------------------------
                    | B. Match by email
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($oldCenter->email)) {

                        $existingCenter = $newDb
                            ->table('partner_centers')
                            ->where('email', $oldCenter->email)
                            ->first();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | C. Match by partner + center name
                    |--------------------------------------------------------------------------
                    */

                    if (!$existingCenter && !empty($oldCenter->center_name)) {

                        $existingCenter = $newDb
                            ->table('partner_centers')
                            ->where('partner_id', $newPartner->id)
                            ->where('name', $oldCenter->center_name)
                            ->first();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 8. DRY RUN
                    |--------------------------------------------------------------------------
                    */

                    if ($dryRun) {

                        if ($existingCenter) {

                            $this->line(
                                "DRY RUN: UPDATE | " .
                                "Old ID: {$oldCenter->id} | " .
                                "Partner: {$partnerCode} | " .
                                "Center: {$oldCenter->center_name} | " .
                                "New ID: {$existingCenter->id}"
                            );

                            $updated++;

                        } else {

                            $this->line(
                                "DRY RUN: INSERT | " .
                                "Old ID: {$oldCenter->id} | " .
                                "Partner: {$partnerCode} | " .
                                "Center: {$oldCenter->center_name}"
                            );

                            $inserted++;
                        }

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 9. UPDATE existing center
                    |--------------------------------------------------------------------------
                    */

                    if ($existingCenter) {

                        $newDb
                            ->table('partner_centers')
                            ->where('id', $existingCenter->id)
                            ->update($data);

                        $updated++;

                        $this->line(
                            "UPDATED: " .
                            "Old ID: {$oldCenter->id} → " .
                            "New ID: {$existingCenter->id} | " .
                            "Partner: {$partnerCode} | " .
                            "Center: {$oldCenter->center_name}"
                        );

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | 10. INSERT new center
                        |--------------------------------------------------------------------------
                        */

                        $newId = $newDb
                            ->table('partner_centers')
                            ->insertGetId($data);

                        $inserted++;

                        $this->line(
                            "INSERTED: " .
                            "Old ID: {$oldCenter->id} → " .
                            "New ID: {$newId} | " .
                            "Partner: {$partnerCode} | " .
                            "Center: {$oldCenter->center_name}"
                        );
                    }

                } catch (Throwable $e) {

                    $failed++;

                    $this->error(
                        "FAILED: Old Center ID: {$oldCenter->id} | " .
                        "Center: {$oldCenter->center_name} | " .
                        $e->getMessage()
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Progress
                |--------------------------------------------------------------------------
                */

                if ($processed % 10 === 0 || $processed === $totalToProcess) {

                    $this->line(
                        "Progress: {$processed}/{$totalToProcess}"
                    );
                }
            }
        }, 'id');

        /*
        |--------------------------------------------------------------------------
        | Final Summary
        |--------------------------------------------------------------------------
        */

        $this->newLine();
        $this->line(str_repeat('=', 45));
        $this->info('Partner Center Migration Completed');
        $this->line(str_repeat('=', 45));

        $this->table(
            ['Type', 'Count'],
            [
                ['Total Old Records', $totalOldRecords],
                ['Processed', $processed],
                ['Inserted', $inserted],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn(
                'DRY RUN completed. No records were inserted or updated.'
            );
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
