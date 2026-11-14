<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateLearnersToWorkspace extends Command
{
    protected $signature = 'learners:migrate-to-workspace
                            {--chunk=500 : Number of learners per batch}';

    protected $description = 'Migrate learners from the old database to the new database while preserving IDs';

    /**
     * Execute the learner migration command.
     */
    public function handle(): int
    {
        $source = DB::connection('old_mysql');
        $destination = DB::connection('mysql');

        $chunkSize = max(1, (int) $this->option('chunk'));

        $sourceCount = $source->table('learners')->count();
        $destinationCount = $destination->table('learners')->count();

        $this->info("Source learners: {$sourceCount}");
        $this->info("Existing destination learners: {$destinationCount}");

        if ($sourceCount === 0) {
            $this->error('No learners found in the source database.');

            return self::FAILURE;
        }

        if (!$this->confirm(
            'Start migration? Existing destination learner IDs will be skipped.'
        )) {
            return self::SUCCESS;
        }

        // Create a separate, timestamped CSV report for oversized phone numbers.
        $phoneLogPath = storage_path(
            'app/logs/skipped_learners_phone_number_' . now()->format('Ymd_His') . '.csv'
        );

        $phoneLogDirectory = dirname($phoneLogPath);

        if (
            !is_dir($phoneLogDirectory)
            && !mkdir($phoneLogDirectory, 0755, true)
            && !is_dir($phoneLogDirectory)
        ) {
            $this->error("Unable to create CSV log directory: {$phoneLogDirectory}");

            return self::FAILURE;
        }

        $csv = fopen($phoneLogPath, 'w');

        if ($csv === false) {
            $this->error("Unable to create CSV log file: {$phoneLogPath}");

            return self::FAILURE;
        }

        fputcsv($csv, [
            'learner_id',
            'first_name',
            'last_name',
            'primary_phone_number',
            'secondary_phone_number',
            'mapped_phone_number',
            'phone_length',
            'skip_reason',
        ]);

        fclose($csv);

        $this->info("Oversized phone number CSV: {$phoneLogPath}");

        $inserted = 0;
        $skipped = 0;
        $phoneSkipped = 0;
        $failed = 0;
        $processed = 0;

        $this->output->progressStart($sourceCount);

        try {
            $source->table('learners')
                ->orderBy('id')
                ->chunkById($chunkSize, function ($learners) use (
                    $destination,
                    $phoneLogPath,
                    &$inserted,
                    &$skipped,
                    &$phoneSkipped,
                    &$failed,
                    &$processed
                ) {
                    $rows = [];

                    $ids = $learners->pluck('id')->all();

                    $existingIds = $destination->table('learners')
                        ->whereIn('id', $ids)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    $existingLookup = array_flip($existingIds);

                    foreach ($learners as $learner) {
                        $processed++;

                        if (isset($existingLookup[(int) $learner->id])) {
                            $skipped++;
                            $this->output->progressAdvance();

                            continue;
                        }

                        // Use the same primary/secondary phone fallback as mapLearner().
                        $phone = $this->firstNonEmpty([
                            $learner->primary_phone_number ?? null,
                            $learner->secondary_phone_number ?? null,
                        ]);

                        // Skip learners whose mapped phone number cannot fit in VARCHAR(20).
                        if ($phone !== null && mb_strlen($phone) > 20) {
                            $this->logSkippedPhoneNumber(
                                $phoneLogPath,
                                $learner,
                                $phone
                            );

                            $phoneSkipped++;
                            $this->output->progressAdvance();

                            continue;
                        }

                        try {
                            $rows[] = $this->mapLearner($learner);
                        } catch (Throwable $exception) {
                            $failed++;

                            Log::error('Learner mapping failed', [
                                'learner_id' => $learner->id,
                                'error' => $exception->getMessage(),
                            ]);

                            $this->output->progressAdvance();

                            continue;
                        }

                        $this->output->progressAdvance();
                    }

                    if (!empty($rows)) {
                        $destination->transaction(function () use (
                            $destination,
                            $rows
                        ) {
                            $destination->table('learners')->insert($rows);
                        });

                        $inserted += count($rows);
                    }
                }, 'id');

        } catch (Throwable $exception) {
            $this->output->progressFinish();

            Log::error('Learner migration stopped', [
                'error' => $exception->getMessage(),
                'processed' => $processed,
                'inserted' => $inserted,
                'skipped_existing_ids' => $skipped,
                'skipped_oversized_phone_numbers' => $phoneSkipped,
                'mapping_failures' => $failed,
                'phone_log_path' => $phoneLogPath,
            ]);

            $this->error($exception->getMessage());
            $this->warn('Already committed batches remain in the destination.');
            $this->warn("Oversized phone number CSV: {$phoneLogPath}");

            return self::FAILURE;
        }

        $this->output->progressFinish();

        $finalCount = $destination->table('learners')->count();

        $nullDates = $destination->table('learners')
            ->whereNull('source_created_at')
            ->count();

        $this->newLine(2);

        $this->info('LEARNER MIGRATION REPORT');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Source learners', $sourceCount],
                ['Inserted learners', $inserted],
                ['Skipped existing IDs', $skipped],
                ['Skipped: phone number over 20 characters', $phoneSkipped],
                ['Mapping failures', $failed],
                ['Final destination learners', $finalCount],
                ['NULL source_created_at', $nullDates],
            ]
        );

        $this->info("Oversized phone number CSV: {$phoneLogPath}");

        if ($nullDates > 0 || $failed > 0) {
            $this->warn('Migration needs attention. Review the report and Laravel log.');

            return self::FAILURE;
        }

        if ($phoneSkipped > 0) {
            $this->warn(
                "{$phoneSkipped} learners were intentionally skipped because their mapped phone number exceeded 20 characters. See the CSV report."
            );
        }

        $this->info('Learner migration completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Map one source learner record to the destination table structure.
     */
    private function mapLearner(object $row): array
    {
        $phone = $this->firstNonEmpty([
            $row->primary_phone_number ?? null,
            $row->secondary_phone_number ?? null,
        ]);

        $email = $this->firstNonEmpty([
            $row->email ?? null,
            $row->USER_EMAIL ?? null,
            $row->primary_email ?? null,
        ]);

        $status = strtolower(trim((string) ($row->status ?? 'active')));

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $gender = strtolower(trim((string) ($row->gender ?? '')));

        if (!in_array($gender, ['male', 'female', 'other'], true)) {
            $gender = null;
        }

        $sourceCreatedAt = $row->create_date
            ?? $this->generateRandomSourceDate();

        return [
            'id' => $row->id,

            // No old association columns exist, so keep these NULL.
            'partner_id' => null,
            'partner_center_id' => null,
            'field_agent_id' => null,

            'status' => $status,
            'course_completed' => (int) ($row->course_completed ?? 0),

            'program_code' => $row->PROGRAM_CODE ?? null,
            'phone_number' => $phone,
            'email' => $email,

            'first_name' => $row->first_name ?? null,
            'last_name' => $row->last_name ?? null,
            'gender' => $gender,
            'date_of_birth' => $row->date_of_birth ?? null,

            'education_level' => $row->education_level ?? null,

            'monthly_family_income_range' =>
                $row->MONTHLY_FAMILY_INCOME_RANGE ?? null,

            'social_category' => $row->SOCIAL_CATEGORY ?? null,
            'religion' => $row->RELIGION ?? null,
            'marital_status' => $row->USER_MARIAL_STATUS ?? null,

            'differently_abled' => $this->toBoolean(
                $row->DIFFRENTLY_ABLED ?? null
            ),

            'digital_proficiency_level' =>
                $row->digital_proficiency ?? null,

            'english_proficiency_level' =>
                $row->english_knowledge ?? null,

            'identity_document' => $row->IDENTITY_DOCUMENTS ?? null,

            'reason_for_learning_new_skills' =>
                $row->REASON_FOR_LEARNING_NEW_SKILLS ?? null,

            'earn_at_my_own_time' => $this->toBoolean(
                $row->EARN_AT_MY_OWN_TIME ?? null
            ),

            'earn_at_my_own_time_hours_per_day' =>
                $row->work_hours_per_day ?? null,

            'earn_at_my_own_time_nature_of_work' =>
                $row->EARN_AT_MY_OWN_TIME ?? null,

            'specific_skill' => $row->preferred_skill1 ?? null,

            'job_preferences_mobility' =>
                $row->job_mobility ?? null,

            'get_a_job_qualification' =>
                isset($row->job_qualifications)
                    ? (is_string($row->job_qualifications)
                        ? $row->job_qualifications
                        : json_encode($row->job_qualifications))
                    : null,

            'get_a_job_when_can_start' =>
                $row->WHEN_CAN_USER_START ?? null,

            'get_a_job_experience' =>
                $row->experiance ?? null,

            'run_a_business' => !empty($row->business_status) ? 1 : 0,

            'run_a_business_status' =>
                $row->business_status ?? null,

            'run_a_business_need_help_with' =>
                $row->USER_NEED_HELP_WITH ?? null,

            'profile_photo_url' => $row->profile_photo_url ?? null,

            'completed_skill_programs' => null,
            'currently_enrolled_program' => null,

            'employment_experience_years' =>
                $row->experience_years ?? null,

            'residential_area_type' => null,

            'interested_in_job' => (int) (
                $row->interested_in_opportunities ?? 0
            ),

            'has_work_experience' => $row->experiance ?? null,

            'city' => $row->DISTRICT_CITY ?? null,
            'state' => $row->STATE ?? null,
            'pin_code' => $row->PIN_CODE ?? null,

            'institute_state' => $row->PROGRAM_STATE ?? null,
            'institute_district' => $row->PROGRAM_DISTRICT ?? null,
            'unit' => $row->UNIT_INSTITUTE ?? null,

            'no_of_pathway_completed' =>
                (int) ($row->no_of_pathway_completed ?? 0),

            'no_of_pathway_enrolled' =>
                (int) ($row->no_of_pathway_enrolled ?? 0),

            'no_of_applied_jobs' =>
                (int) ($row->no_of_applied_jobs ?? 0),

            'no_of_applied_courses' =>
                (int) ($row->no_of_applied_courses ?? 0),

            'source_created_at' => $sourceCreatedAt,

            // normalized_mobile is generated by MySQL.
            'created_at' => $row->created_at ?? null,
            'updated_at' => $row->updated_at ?? null,

            'partner_wise_course_counts' =>
                $row->partner_wise_course_counts ?? null,

            'partner_wise_job_counts' =>
                $row->partner_wise_job_counts ?? null,
        ];
    }

    /**
     * Append a skipped learner's phone details to the oversized-phone CSV report.
     */
    private function logSkippedPhoneNumber(
        string $filePath,
        object $learner,
        string $phoneNumber
    ): void {
        $csv = fopen($filePath, 'a');

        if ($csv === false) {
            throw new \RuntimeException("Unable to append to CSV log: {$filePath}");
        }

        fputcsv($csv, [
            $learner->id,
            $learner->first_name ?? '',
            $learner->last_name ?? '',
            $learner->primary_phone_number ?? '',
            $learner->secondary_phone_number ?? '',
            $phoneNumber,
            mb_strlen($phoneNumber),
            'Phone number exceeds 20 characters',
        ]);

        fclose($csv);
    }

    /**
     * Generate a random valid datetime between January 2024 and December 2025.
     */
    private function generateRandomSourceDate(): string
    {
        $start = Carbon::create(2024, 1, 1, 0, 0, 0);
        $end = Carbon::create(2025, 12, 31, 23, 59, 59);

        $randomTimestamp = random_int(
            $start->timestamp,
            $end->timestamp
        );

        return Carbon::createFromTimestamp($randomTimestamp)
            ->format('Y-m-d H:i:s');
    }

    /**
     * Return the first non-empty value from a list of candidates.
     */
    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Convert common yes/no text values into a database boolean.
     */
    private function toBoolean($value): int
    {
        if (is_bool($value) || is_numeric($value)) {
            return (int) ((int) $value === 1);
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, [
            'yes',
            'true',
            '1',
            'y',
            'enabled',
            'active',
        ], true) ? 1 : 0;
    }
}
