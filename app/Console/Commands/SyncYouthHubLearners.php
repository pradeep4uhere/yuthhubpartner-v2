<?php

namespace App\Console\Commands;

use App\Models\Learner;
use App\Models\LearnerSyncLog;
use App\Models\LearnerSyncPageLog;
use App\Models\LearnerSyncRecordLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncYouthHubLearners extends Command
{
   protected $signature = 'learners:sync
    {--full : Fetch the complete YouthHub dataset}
    {--run-id= : Use an existing synchronization run ID}';

    protected $description = 'Safely synchronize learners from the YouthHub API';

    /** Execute a full or incremental YouthHub learner synchronization. */
    public function handle(): int
    {
        $startedAt = now();
        $fullSync = (bool) $this->option('full');
        $page = 1;
        $perPage = 1000;

        $stats = [
            'total_fetched' => 0,
            'total_inserted' => 0,
            'total_updated' => 0,
            'total_unchanged' => 0,
            'total_duplicates' => 0,
            'total_failed' => 0,
            'total_processed' => 0,
            'total_pages' => 0,
        ];

        // Reuse the synchronization log created by the dashboard or create one for manual Artisan execution.
        $runId = $this->option('run-id');

        if ($runId) {
            $syncRun = LearnerSyncLog::where('run_id', $runId)->firstOrFail();

            if ($syncRun->status !== 'running') {
                $this->error('This synchronization run is not in running status.');

                return Command::FAILURE;
            }
        } else {
            $syncRun = LearnerSyncLog::create([
                'run_id' => (string) Str::uuid(),
                'sync_type' => $fullSync ? 'full' : 'incremental',
                'status' => 'running',
                'started_at' => $startedAt,
            ]);
        }

        try {
            $this->info('Starting YouthHub Learner Sync...');
            $this->info('Sync Type: ' . ($fullSync ? 'FULL' : 'INCREMENTAL'));

            $baseUrl = rtrim((string) config('services.youthhub.base_url'), '/');
            $username = config('services.youthhub.username');
            $password = config('services.youthhub.password');

            if (!$baseUrl || !$username || !$password) {
                throw new \RuntimeException(
                    'YouthHub API configuration is missing. Check config/services.php and .env.'
                );
            }

            // Authenticate with the YouthHub API and retrieve an access token.
            $loginResponse = Http::acceptJson()
                ->timeout(30)
                ->connectTimeout(15)
                ->retry(3, 2000)
                ->post($baseUrl . '/login', [
                    'username' => $username,
                    'password' => $password,
                ]);

            if (!$loginResponse->successful()) {
                throw new \RuntimeException(
                    'YouthHub login failed with HTTP status ' . $loginResponse->status()
                );
            }

            $token = $loginResponse->json('accessToken');

            if (!$token) {
                throw new \RuntimeException('YouthHub accessToken was not returned.');
            }

            $token = preg_replace('/^Bearer\s+/i', '', trim($token));

            // Calculate an overlapping incremental cursor to avoid missing same-second records.
            $ma = null;

            if (!$fullSync) {
                $lastCreatedAt = Learner::max('source_created_at');

                if ($lastCreatedAt) {
                    $ma = Carbon::parse($lastCreatedAt)
                        ->subSecond()
                        ->getTimestamp();
                }
            }

            $this->info('Incremental cursor: ' . ($ma ?? 'Not applied'));

            do {
                $this->info("Fetching page {$page}...");

                $params = [
                    'pn' => $page,
                    'ps' => $perPage,
                ];

                if ($ma !== null) {
                    $params['ma'] = $ma;
                }

                // Fetch one page of learner profiles from the API.
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(120)
                    ->retry(3, 2000)
                    ->get($baseUrl . '/gpud', $params);

                if (!$response->successful()) {
                    LearnerSyncPageLog::create([
                        'learner_sync_log_id' => $syncRun->id,
                        'page_number' => $page,
                        'status' => 'failed',
                        'error_message' => 'API HTTP status ' . $response->status(),
                        'fetched_at' => now(),
                    ]);

                    throw new \RuntimeException(
                        "YouthHub API request failed on page {$page}. HTTP status: " .
                        $response->status()
                    );
                }

                $responseData = $response->json();
                $profiles = $responseData['data'] ?? [];

                if (!is_array($profiles)) {
                    throw new \RuntimeException("Invalid API data format on page {$page}.");
                }

                $recordsFetched = count($profiles);
                $totalRecords = (int) ($responseData['totalRecordCount'] ?? 0);
                $recordsRemaining = max(
                    $totalRecords - ($page * $perPage),
                    0
                );

                $stats['total_fetched'] += $recordsFetched;
                $stats['total_pages']++;

                $pageLog = LearnerSyncPageLog::create([
                    'learner_sync_log_id' => $syncRun->id,
                    'page_number' => $page,
                    'records_fetched' => $recordsFetched,
                    'total_records' => $totalRecords,
                    'records_remaining' => $recordsRemaining,
                    'status' => 'success',
                    'fetched_at' => now(),
                ]);

                $this->info(
                    "Fetched: {$recordsFetched} | Total: {$totalRecords} | Remaining: {$recordsRemaining}"
                );

                // Collect valid mobile numbers so existing learners can be loaded in one query per page.
                $preparedProfiles = [];
                $mobileNumbers = [];

                foreach ($profiles as $user) {
                    $profile = $user['profileInfo'] ?? null;

                    if (!is_array($profile) || empty($profile)) {
                        $stats['total_failed']++;
                        $stats['total_processed']++;

                        $this->logRecordIssue(
                            $syncRun->id,
                            $page,
                            'failed',
                            null,
                            null,
                            [],
                            'Missing or invalid profileInfo.',
                        );

                        continue;
                    }

                    $phone = $profile['user_phone_number'] ?? null;
                    $mobile = $this->normalizeMobile($phone);

                    if (!$mobile) {
                        $stats['total_failed']++;
                        $stats['total_processed']++;

                        $this->logRecordIssue(
                            $syncRun->id,
                            $page,
                            'failed',
                            $phone,
                            null,
                            $profile,
                            'Missing or invalid 10-digit mobile number.',
                        );

                        continue;
                    }

                    $preparedProfiles[] = [
                        'profile' => $profile,
                        'mobile' => $mobile,
                    ];

                    $mobileNumbers[] = $mobile;
                }

                $mobileNumbers = array_values(array_unique($mobileNumbers));

                // Load existing learner records for the current page without changing local relationships.
                $existingRecords = [];

                if (!empty($mobileNumbers)) {
                    $existingRecords = DB::table('learners')
                        ->whereIn('normalized_mobile', $mobileNumbers)
                        ->get()
                        ->groupBy('normalized_mobile')
                        ->map(fn ($records) => $records->all())
                        ->all();
                }

                foreach ($preparedProfiles as $item) {
                    $profile = $item['profile'];
                    $mobile = $item['mobile'];

                    $stats['total_processed']++;

                    try {
                        $payload = $this->mapProfile($profile, $mobile);

                        $candidates = $existingRecords[$mobile] ?? [];

                        $exactMatches = [];

                        foreach ($candidates as $candidate) {
                            if (
                                $this->makeIdentity($candidate) ===
                                $this->makeIdentity($payload)
                            ) {
                                $exactMatches[] = $candidate;
                            }
                        }

                        // Skip records when the composite identity matches multiple existing learners.
                        if (count($exactMatches) > 1) {
                            $stats['total_duplicates']++;

                            $this->logRecordIssue(
                                $syncRun->id,
                                $page,
                                'duplicate',
                                $profile['user_phone_number'] ?? null,
                                $mobile,
                                $profile,
                                'Multiple existing learners match the same composite identity.',
                                array_map(fn ($record) => $record->id, $exactMatches),
                            );

                            continue;
                        }

                        // Skip same-mobile records when their identity cannot be safely matched.
                        if (count($exactMatches) === 0 && count($candidates) > 0) {
                            $stats['total_duplicates']++;

                            $this->logRecordIssue(
                                $syncRun->id,
                                $page,
                                'ambiguous',
                                $profile['user_phone_number'] ?? null,
                                $mobile,
                                $profile,
                                'Mobile number exists, but composite identity does not match.',
                                array_map(fn ($record) => $record->id, $candidates),
                            );

                            continue;
                        }

                        if (count($exactMatches) === 1) {
                            $existing = $exactMatches[0];

                           // Preserve existing database values whenever the API does not provide a value.
                            $updatePayload = array_filter(
                                $payload,
                                fn ($value) => $value !== null
                            );

                            if ($this->isUnchanged($existing, $updatePayload)) {
                                $stats['total_unchanged']++;
                                continue;
                            }

                            // Update only non-null API values and preserve local learner relationships.
                            DB::table('learners')
                                ->where('id', $existing->id)
                                ->update(array_merge($updatePayload, [
                                    'updated_at' => now(),
                                ]));

                           // Keep the in-memory learner record consistent with the values actually saved in the database.
                            foreach ($updatePayload as $key => $value) {
                                $existing->{$key} = $value;
                            }

                            $stats['total_updated']++;
                            continue;
                        }

                        // Insert a new learner only when no existing record has this mobile number.
                        $learnerId = DB::table('learners')->insertGetId(
                            array_merge($payload, [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ])
                        );

                        $newRecord = (object) array_merge(
                            ['id' => $learnerId],
                            $payload
                        );

                        $existingRecords[$mobile][] = $newRecord;
                        $stats['total_inserted']++;

                    } catch (Throwable $recordException) {
                        $stats['total_failed']++;

                        $this->logRecordIssue(
                            $syncRun->id,
                            $page,
                            'failed',
                            $profile['user_phone_number'] ?? null,
                            $mobile,
                            $profile,
                            $recordException->getMessage(),
                        );

                        Log::error('YouthHub learner record sync failed', [
                            'run_id' => $syncRun->run_id,
                            'page' => $page,
                            'mobile' => $mobile,
                            'error' => $recordException->getMessage(),
                        ]);
                    }
                }

                // Persist cumulative metrics after every page so the dashboard can display live progress.
                $stats['total_success'] = (
                    $stats['total_inserted'] +
                    $stats['total_updated'] +
                    $stats['total_unchanged']
                );

                $syncRun->update(array_merge($stats, [
                    'status' => 'running',
                ]));

                $this->info(
                    "Page {$page} completed | Inserted: {$stats['total_inserted']} | " .
                    "Updated: {$stats['total_updated']} | " .
                    "Unchanged: {$stats['total_unchanged']} | " .
                    "Duplicates: {$stats['total_duplicates']} | " .
                    "Failed: {$stats['total_failed']}"
                );

                $page++;

                // Stop if the API returns an empty page or the reported dataset is exhausted.
                if ($recordsFetched === 0 || $recordsRemaining === 0) {
                    break;
                }

            } while (true);

            $duration = max(0, now()->timestamp - $startedAt->timestamp);

            $stats['total_success'] = (
                $stats['total_inserted'] +
                $stats['total_updated'] +
                $stats['total_unchanged']
            );

            $syncRun->update(array_merge($stats, [
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => $duration,
            ]));

            $this->newLine();
            $this->info('========================================');
            $this->info('YouthHub Learner Sync Completed');
            $this->info('========================================');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Fetched', $stats['total_fetched']],
                    ['Total Processed', $stats['total_processed']],
                    ['Inserted', $stats['total_inserted']],
                    ['Updated', $stats['total_updated']],
                    ['Unchanged', $stats['total_unchanged']],
                    ['Duplicates / Ambiguous', $stats['total_duplicates']],
                    ['Failed', $stats['total_failed']],
                    ['Total Pages', $stats['total_pages']],
                    ['Duration (seconds)', $duration],
                ]
            );

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $duration = max(0, now()->timestamp - $startedAt->timestamp);

            $syncRun->update(array_merge($stats, [
                'status' => 'failed',
                'completed_at' => now(),
                'duration_seconds' => $duration,
                'error_message' => $e->getMessage(),
            ]));

            Log::error('YouthHub learner synchronization failed', [
                'run_id' => $syncRun->run_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->error('YouthHub Sync Failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /** Convert a phone number into the ten-digit mobile identity used by the database. */
    private function normalizeMobile($phone): ?string
    {
        $mobile = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, -10);
        }

        return strlen($mobile) === 10 ? $mobile : null;
    }

    /** Convert an API profile into the database columns managed by the source API. */
    private function mapProfile(array $profile, string $mobile): array
    {
        $gender = strtolower(trim((string) ($profile['gender'] ?? '')));

        if (!in_array($gender, ['male', 'female', 'other'], true)) {
            $gender = null;
        }

        $education = $profile['education_level'] ?? null;

        return [
                'normalized_mobile' => $mobile,
                'phone_number' => $this->cleanValue($profile['user_phone_number'] ?? null),
                'first_name' => $this->cleanValue($profile['first_name'] ?? null),
                'last_name' => $this->cleanValue($profile['last_name'] ?? null),
                'gender' => $gender,
                'date_of_birth' => $this->normalizeDob($profile['date_of_birth'] ?? null),
                'education_level' => $this->encodeValue($education),
                'monthly_family_income_range' => $this->cleanValue($profile['monthly_family_income_range'] ?? null),
                'email' => $this->cleanValue($profile['user_email'] ?? null),
                'city' => $this->cleanValue($profile['city'] ?? null),
                'state' => $this->cleanValue($profile['loc_state'] ?? null),
                'pin_code' => $this->cleanValue($profile['pin_code'] ?? null),
                'program_code' => $this->cleanValue($profile['program_code'] ?? null),
                'institute_state' => $this->cleanValue($profile['institute_state'] ?? null),
                'institute_district' => $this->cleanValue($profile['institute_district'] ?? null),
                'unit' => $this->cleanValue($profile['unit'] ?? null),
                'social_category' => $this->cleanValue($profile['social_category'] ?? null),
                'religion' => $this->cleanValue($profile['religion'] ?? null),
                'marital_status' => $this->cleanValue($profile['marital_status'] ?? null),

                // Preserve existing values when the API does not provide these fields.
                'differently_abled' => $this->booleanValue($profile['differently_abled'] ?? null),
                'digital_proficiency_level' => $this->cleanValue($profile['digital_proficiency_level'] ?? null),
                'english_proficiency_level' => $this->cleanValue($profile['engilsh_proficiency_level'] ?? null),
                'identity_document' => $this->encodeValue($profile['identity_document'] ?? null),
                'reason_for_learning_new_skills' => $this->encodeValue($profile['reason_for_learning_new_skills'] ?? null),
                'earn_at_my_own_time' => $this->booleanValue($profile['earn_at_my_own_time'] ?? null),
                'earn_at_my_own_time_hours_per_day' => $profile['earn_at_my_own_time_hours_per_day'] ?? null,
                'earn_at_my_own_time_nature_of_work' => $this->encodeValue($profile['earn_at_my_own_time_nature_of_work'] ?? null),
                'specific_skill' => $this->encodeValue($profile['specific_skill'] ?? null),
                'job_preferences_mobility' => $this->cleanValue($profile['job_preferences_mobility'] ?? null),
                'get_a_job_qualification' => $this->cleanValue($profile['get_a_job_qualification'] ?? null),
                'get_a_job_when_can_start' => $this->cleanValue($profile['get_a_job_when_can_start'] ?? null),
                'get_a_job_experience' => $this->cleanValue(
                    $profile['get_a_job_experiance'] ?? $profile['get_a_job_experience'] ?? null
                ),
                'run_a_business' => $this->booleanValue($profile['run_a_business'] ?? null),
                'run_a_business_status' => $this->cleanValue($profile['run_a_business_status'] ?? null),
                'run_a_business_need_help_with' => $this->encodeValue($profile['run_a_business_need_help_with'] ?? null),
                'profile_photo_url' => $this->cleanValue($profile['profile_photo_url'] ?? null),
                'completed_skill_programs' => $this->encodeValue($profile['completed_skill_programs'] ?? null),
                'currently_enrolled_program' => $this->cleanValue($profile['currently_enrolled_program'] ?? null),
                'employment_experience_years' => $this->cleanValue($profile['employment_experience_years'] ?? null),
                'residential_area_type' => $this->cleanValue($profile['residential_area_type'] ?? null),

                'interested_in_job' => $this->booleanValue($profile['interested_in_job'] ?? null) ?? 0,
                'has_work_experience' => $this->cleanValue($profile['has_work_experience'] ?? null),

                // Preserve existing counters when the API omits them.
                'no_of_pathway_completed' => isset($profile['no_of_pathway_completed'])
                    ? (int) $profile['no_of_pathway_completed']
                    : null,

                'no_of_pathway_enrolled' => isset($profile['no_of_pathway_enrolled'])
                    ? (int) $profile['no_of_pathway_enrolled']
                    : null,

                'no_of_applied_jobs' => isset($profile['no_of_applied_jobs'])
                    ? (int) $profile['no_of_applied_jobs']
                    : null,

                'no_of_applied_courses' => isset($profile['no_of_applied_courses'])
                    ? (int) $profile['no_of_applied_courses']
                    : null,

                'partner_wise_course_counts' => $this->encodeValue(
                    $profile['partner_wise_course_counts'] ?? null
                ),

                'partner_wise_job_counts' => $this->encodeValue(
                    $profile['partner_wise_job_counts'] ?? null
                ),

                'source_created_at' => $this->normalizeSourceDate($profile['create_date'] ?? null),
            ];
    }

    /** Normalize a source date of birth from the API's day/month/year format. */
    private function normalizeDob($value): ?string
    {
        $value = $this->cleanValue($value);

        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    /** Convert the API creation date into a database-compatible timestamp. */
    private function normalizeSourceDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    /** Normalize optional text values and convert blank strings into null. */
    private function cleanValue($value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** Encode array values for storage in text columns while preserving scalar values. */
    private function encodeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return $this->cleanValue($value);
    }

   /** Convert API boolean values into zero, one, or null when the API omits the value. */
    private function booleanValue($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }


    /** Build a normalized composite identity from an API profile or database learner. */
    private function makeIdentity($record): string
    {
        $get = function ($key) use ($record) {
            if (is_array($record)) {
                return $record[$key] ?? null;
            }

            return $record->{$key} ?? null;
        };

        // Treat common placeholder values as missing data during identity matching.
        $normalizeText = function ($value) {
            $value = mb_strtolower(trim((string) ($value ?? '')));

            if (in_array($value, ['na', 'n/a', 'null', 'none', '-', '--'], true)) {
                return '';
            }

            return $value;
        };

        $dob = $get('date_of_birth');

        if ($dob) {
            try {
                $dob = Carbon::parse($dob)->format('Y-m-d');
            } catch (Throwable $e) {
                $dob = '';
            }
        }

        return hash('sha256', implode('|', [
            preg_replace('/\D+/', '', (string) $get('normalized_mobile')),
            $normalizeText($get('first_name')),
            $normalizeText($get('last_name')),
            (string) ($dob ?? ''),
            $normalizeText($get('email')),
        ]));
    }


    /** Determine whether every API-managed field already has the incoming value. */
    private function isUnchanged(object $existing, array $payload): bool
    {
        foreach ($payload as $key => $value) {
            $oldValue = $existing->{$key} ?? null;

            if ((string) ($oldValue ?? '') !== (string) ($value ?? '')) {
                return false;
            }
        }

        return true;
    }

    /** Save a duplicate, ambiguous, or failed learner record for later review. */
    private function logRecordIssue(
        int $syncLogId,
        int $page,
        string $type,
        $phone,
        ?string $mobile,
        array $profile,
        string $reason,
        array $matchingIds = []
    ): void {
        LearnerSyncRecordLog::create([
            'learner_sync_log_id' => $syncLogId,
            'page_number' => $page,
            'record_type' => $type,
            'phone_number' => $phone,
            'normalized_mobile' => $mobile,
            'matching_learner_ids' => $matchingIds,
            'source_payload' => [
                'first_name' => $profile['first_name'] ?? null,
                'last_name' => $profile['last_name'] ?? null,
                'date_of_birth' => $profile['date_of_birth'] ?? null,
                'user_email' => $profile['user_email'] ?? null,
                'user_phone_number' => $profile['user_phone_number'] ?? null,
            ],
            'reason' => $reason,
        ]);
    }
}
