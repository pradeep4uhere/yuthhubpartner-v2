<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\LearnerSyncLog;
use App\Models\Learner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class SyncYouthHubLearners extends Command
{

    protected $signature = 'learners:sync';

    protected $description = 'Sync learners from YouthHub API';

public function handle()
{
    $this->info('Starting YouthHub Learner Sync...');

    $page = 1;
    $perPage = 1000;

    $totalFetched  = 0;
    $totalInserted = 0;
    $totalUpdated  = 0;
    $ma = '';


    $syncRun = LearnerSyncLog::create([
        'status'     => 'running',
        'started_at' => now(),
    ]);

    try {

        /*
        |--------------------------------------------------------------------------
        | 1. YouthHub Login
        |--------------------------------------------------------------------------
        */

        $loginResponse = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->timeout(30)
        ->connectTimeout(20)
        ->retry(3, 2000)
        ->post('https://youthhub.org/api/login', [
            'username' => 'powerbi',
            'password' => 'MtufISqYihHdftiS',
        ]);

        if (!$loginResponse->successful()) {

            throw new \Exception(
                'YouthHub login failed: ' . $loginResponse->body()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Get Access Token
        |--------------------------------------------------------------------------
        */

        $token = $loginResponse->json('accessToken');

        if (!$token) {
            throw new \Exception('YouthHub accessToken not found.');
        }

        // API response contains: Bearer eyJ...
        $cleanToken = str_replace('Bearer ', '', $token);

        /*
        |--------------------------------------------------------------------------
        | 3. Get Last Created Date
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | First sync => NULL
        | Subsequent sync => create_date + 1 second as epoch timestamp
        |
        */

        $lastCreatedAt = Learner::max('source_created_at');

        Log::info('YouthHub Max Create Date', [
            'last_created_at' => $lastCreatedAt,
        ]);

        if ($lastCreatedAt) {

            $ma = Carbon::parse($lastCreatedAt)
                ->addSecond()
                ->timestamp;

        } else {

            // IMPORTANT: API expects epoch when ma is supplied.
            // First sync should send NULL.
            $ma = strtotime('2020-09-01 00:00:00');
        }
        //dd($ma);
        Log::info('YouthHub API MA', [
            'ma' => $ma,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Fetch Pages
        |--------------------------------------------------------------------------
        */

        $totalRecords = 0;

        do {

            $this->info("Fetching page: {$page}");

            $params = [
                'pn' => $page,
                'ps' => $perPage,
            ];

            //dd( $params);

            /*
             * Don't send ma when it is NULL.
             *
             * This is important because on first sync:
             * ma = NULL
             */
            if ($ma !== null) {
                $params['ma'] = $ma;
            }

            Log::info('YouthHub API Request', [
                'page'   => $page,
                'params' => $params,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 5. API Request
            |--------------------------------------------------------------------------
            */
            //dd($params);
            $userResponse = Http::withToken($cleanToken)
                ->timeout(120)
                ->retry(3, 2000)
                ->get(
                    'https://youthhub.org/api/gpud',
                    $params
                );

            if (!$userResponse->successful()) {

                Log::error('YouthHub API Failed', [
                    'page'   => $page,
                    'status' => $userResponse->status(),
                    'body'   => $userResponse->body(),
                ]);

                throw new \Exception(
                    "YouthHub API fetch failed on page {$page}: " .
                    $userResponse->body()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Parse API Response
            |--------------------------------------------------------------------------
            */

            $responseData = $userResponse->json();

            $profiles = $responseData['data'] ?? [];

            $recordsFetched = count($profiles);

            $totalRecords = (int) (
                $responseData['totalRecordCount'] ?? 0
            );

            $recordsRemaining = max(
                $totalRecords - ($page * $perPage),
                0
            );

            $totalFetched += $recordsFetched;

            $this->info(
                "Fetched: {$recordsFetched} | " .
                "Total: {$totalRecords} | " .
                "Remaining: {$recordsRemaining}"
            );

            Log::info('YouthHub API Response', [
                'page'             => $page,
                'records_fetched'  => $recordsFetched,
                'total_records'    => $totalRecords,
                'records_remaining'=> $recordsRemaining,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. Prepare Bulk Upsert Data
            |--------------------------------------------------------------------------
            */

           $upsertData = [];

foreach ($profiles as $user) {

    $profile = $user['profileInfo'] ?? [];

    if (empty($profile)) {
        continue;
    }

    $phone = $profile['user_phone_number'] ?? null;

    if (empty($phone)) {
        continue;
    }

    $normalizedMobile = preg_replace(
        '/\D+/',
        '',
        $phone
    );

    /*
    |--------------------------------------------------------------------------
    | Gender
    |--------------------------------------------------------------------------
    */

    $gender = ucfirst(
        strtolower(
            $profile['gender'] ?? 'Male'
        )
    );

    if (!in_array($gender, [
        'Male',
        'Female',
        'Other'
    ])) {
        $gender = 'Male';
    }

    /*
    |--------------------------------------------------------------------------
    | DOB
    |--------------------------------------------------------------------------
    */

    $dob = $profile['date_of_birth'] ?? null;

    if ($dob) {

        $dob = urldecode($dob);

        $dob = preg_replace(
            '/[^0-9\/]/',
            '',
            $dob
        );

        try {

            $dob = !empty($dob)
                ? Carbon::createFromFormat(
                    'd/m/Y',
                    $dob
                )->format('Y-m-d')
                : null;

        } catch (\Throwable $e) {

            $dob = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Education
    |--------------------------------------------------------------------------
    */

    $educationLevel = $profile['education_level'] ?? null;

    if (is_array($educationLevel)) {
        $educationLevel = json_encode($educationLevel);
    }

    /*
    |--------------------------------------------------------------------------
    | Build DB Row
    |--------------------------------------------------------------------------
    */

    $upsertData[] = [

        'normalized_mobile' =>
            $normalizedMobile,

        'phone_number' =>
            $phone,

        'first_name' =>
            $profile['first_name'] ?? 'NA',

        'last_name' =>
            $profile['last_name'] ?? 'NA',

        'gender' =>
            $gender,

        'date_of_birth' =>
            $dob,

        'education_level' =>
            $educationLevel,

        'monthly_family_income_range' =>
            $profile['monthly_family_income_range'] ?? null,

        'email' =>
            $profile['user_email'] ?? null,

        'city' =>
            $profile['city'] ?? null,

        'state' =>
            $profile['loc_state'] ?? null,

        'pin_code' =>
            $profile['pin_code'] ?? null,

        'program_code' =>
            $profile['program_code'] ?? null,

        'institute_state' =>
            $profile['institute_state'] ?? null,

        'institute_district' =>
            $profile['institute_district'] ?? null,

        'unit' =>
            $profile['unit'] ?? null,

        'social_category' =>
            $profile['social_category'] ?? null,

        'religion' =>
            $profile['religion'] ?? null,

        'marital_status' =>
            $profile['marital_status'] ?? null,

        'differently_abled' =>
            $profile['differently_abled'] ?? 0,

        'digital_proficiency_level' =>
            $profile['digital_proficiency_level'] ?? 0,

        'english_proficiency_level' =>
            $profile['engilsh_proficiency_level'] ?? 0,

        'identity_document' =>
            $profile['identity_document'] ?? null,

        'job_preferences_mobility' =>
            $profile['job_preferences_mobility'] ?? null,

        'profile_photo_url' =>
            $profile['profile_photo_url'] ?? null,

        'completed_skill_programs' =>
            $profile['completed_skill_programs'] ?? null,

        'currently_enrolled_program' =>
            $profile['currently_enrolled_program'] ?? null,

        'residential_area_type' =>
            $profile['residential_area_type'] ?? null,

        'interested_in_job' =>
            $profile['interested_in_job'] ?? 0,

        'no_of_pathway_completed' =>
            $profile['no_of_pathway_completed'] ?? null,

        'no_of_pathway_enrolled' =>
            $profile['no_of_pathway_enrolled'] ?? null,

        'no_of_applied_jobs' =>
            $profile['no_of_applied_jobs'] ?? null,

        'no_of_applied_courses' =>
            $profile['no_of_applied_courses'] ?? null,

        'partner_wise_course_counts' =>
            json_encode(
                $profile['partner_wise_course_counts'] ?? []
            ),

        'partner_wise_job_counts' =>
            json_encode(
                $profile['partner_wise_job_counts'] ?? []
            ),
        'source_created_at' => !empty($profile['create_date'])
            ? date('Y-m-d H:i:s', strtotime($profile['create_date']))
            : null,

        'created_at' =>
            now(),

        'updated_at' =>
            now(),
    ];
}

$this->info(
    'Prepared rows: ' . count($upsertData)
);

            /*
            |--------------------------------------------------------------------------
            | 8. Bulk Upsert
            |--------------------------------------------------------------------------
            */

            $this->info('Prepared rows: ' . count($upsertData));

            Log::info('Prepared Learner Rows', [
                'count' => count($upsertData),
                'first_row' => $upsertData[0] ?? null,
            ]);

            if (!empty($upsertData)) {

    $this->info(
        'Prepared rows for DB: ' . count($upsertData)
    );

    try {

        /*
        |--------------------------------------------------------------------------
        | Split into chunks to avoid memory issue
        |--------------------------------------------------------------------------
        */

        $chunks = array_chunk($upsertData, 250);

        foreach ($chunks as $rows) {

            $this->info(
                'Upserting DB chunk: ' . count($rows)
            );

            Learner::upsert(
                $rows,
                [
                    'normalized_mobile'
                ],
                [
                    'phone_number',
                    'first_name',
                    'last_name',
                    'gender',
                    'date_of_birth',
                    'education_level',
                    'monthly_family_income_range',
                    'email',
                    'city',
                    'state',
                    'pin_code',
                    'program_code',
                    'institute_state',
                    'institute_district',
                    'unit',
                    'social_category',
                    'religion',
                    'marital_status',
                    'differently_abled',
                    'digital_proficiency_level',
                    'english_proficiency_level',
                    'identity_document',
                    'job_preferences_mobility',
                    'profile_photo_url',
                    'completed_skill_programs',
                    'currently_enrolled_program',
                    'residential_area_type',
                    'interested_in_job',
                    'no_of_pathway_completed',
                    'no_of_pathway_enrolled',
                    'no_of_applied_jobs',
                    'no_of_applied_courses',
                    'partner_wise_course_counts',
                    'partner_wise_job_counts',
                    'source_created_at',
                    'updated_at',
                ]
            );

            $totalUpdated += count($rows);

            unset($rows);

            gc_collect_cycles();
        }

        /*
        |--------------------------------------------------------------------------
        | Clear page data from memory
        |--------------------------------------------------------------------------
        */

        unset($chunks);
        unset($upsertData);

        gc_collect_cycles();

    } catch (\Throwable $e) {

        Log::error('YouthHub Learner DB Upsert Failed', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        $this->error(
            'DB Upsert Error: ' . $e->getMessage()
        );

        throw $e;
    }
}
            /*
            |--------------------------------------------------------------------------
            | 9. Sync Log
            |--------------------------------------------------------------------------
            */

            Log::info('YouthHub Page Sync Completed', [
                'page'              => $page,
                'records_fetched'   => $recordsFetched,
                'inserted'          => $totalInserted,
                'updated'           => $totalUpdated,
            ]);

            $page++;

        } while ($recordsRemaining > 0);

        /*
        |--------------------------------------------------------------------------
        | 10. Sync Completed
        |--------------------------------------------------------------------------
        */


        $syncRun->update([
            'status'        => 'completed',
            'completed_at'  => now(),
            'total_fetched' => $totalFetched,
            'total_inserted'=> $totalInserted,
            'total_updated' => $totalUpdated,
        ]);

        $this->info('');
        $this->info('======================================');
        $this->info('YouthHub Sync Completed Successfully');
        $this->info('======================================');
        $this->info("Total Fetched : {$totalFetched}");
        $this->info("Total Inserted: {$totalInserted}");
        $this->info("Total Updated : {$totalUpdated}");

    } catch (\Throwable $e) {

        $syncRun->update([
            'status'       => 'failed',
            'completed_at' => now(),
            'error_message'=> $e->getMessage(),
        ]);

        Log::error('YouthHub Learner Sync Exception', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        $this->error('');
        $this->error('YouthHub Sync Failed!');
        $this->error($e->getMessage());

        return Command::FAILURE;
    }

    return Command::SUCCESS;
}


}
