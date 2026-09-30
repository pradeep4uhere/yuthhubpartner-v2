<?php

namespace Database\Seeders;

use App\Models\EventTransaction;
use App\Models\FieldAgent;
use App\Models\Learner;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use Illuminate\Database\Seeder;

class EventTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $learners = Learner::query()
            ->where('status', 'active')
            ->take(10)
            ->get();

        $fieldAgents = FieldAgent::query()
            ->where('status', 'active')
            ->take(10)
            ->get();

        $partners = Partner::query()
            ->take(10)
            ->get();

        $partnerCenters = PartnerCenter::query()
            ->take(10)
            ->get();

        $placementUsers = PartnerPlacementUser::query()
            ->take(10)
            ->get();

        if ($learners->isEmpty()) {
            $this->command->error('No learners found.');
            return;
        }

        if ($fieldAgents->isEmpty()) {
            $this->command->error('No active field agents found.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Sample workflow statuses
        |--------------------------------------------------------------------------
        */

        $statuses = [
            'draft',
            'submitted',
            'under_review',
            'approved',
            'rejected',
            'resubmitted',
        ];

        foreach ($learners as $index => $learner) {

            $fieldAgent = $fieldAgents[$index % $fieldAgents->count()];

            $partner = $partners->isNotEmpty()
                ? $partners[$index % $partners->count()]
                : null;

            $partnerCenter = $partnerCenters->isNotEmpty()
                ? $partnerCenters[$index % $partnerCenters->count()]
                : null;

            $placementUser = $placementUsers->isNotEmpty()
                ? $placementUsers[$index % $placementUsers->count()]
                : null;

            $workflowStatus = $statuses[$index % count($statuses)];

            $transaction = EventTransaction::create([
                'learner_id' => $learner->id,

                'partner_id' => $partner?->id,

                'partner_center_id' => $partnerCenter?->id,

                'partner_placement_user_id' => $placementUser?->id,

                'field_agent_id' => $fieldAgent->id,

                'review_status' => match ($workflowStatus) {
                    'under_review' => 'under_review',
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    'resubmitted' => 'resubmitted',
                    default => null,
                },

                'workflow_status' => $workflowStatus,

                'beneficiary_phone_number' =>
                    $learner->normalized_mobile
                    ?: $learner->phone_number,

                'beneficiary_name' => trim(
                    ($learner->first_name ?? '') . ' ' .
                    ($learner->last_name ?? '')
                ),

                'event_master_id' => 'EM-' . str_pad(
                    $index + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

                'event_master_type_id' => 'EMT-' . str_pad(
                    $index + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

                'event_type_name' => [
                    'Employment',
                    'Training',
                    'Placement',
                    'Apprenticeship',
                    'Career Counselling',
                ][$index % 5],

                'event_category_name' => [
                    'Job Placement',
                    'Skill Development',
                    'Career Support',
                    'Industry Connect',
                    'Employment Support',
                ][$index % 5],

                'event_create_date' => now()
                    ->subDays($index + 1)
                    ->toDateString(),

                'event_date_submitted' => in_array(
                    $workflowStatus,
                    [
                        'submitted',
                        'under_review',
                        'approved',
                        'rejected',
                        'resubmitted',
                    ]
                )
                    ? now()->subDays($index)->toDateString()
                    : null,

                'event_value' => [
                    500,
                    750,
                    1000,
                    1500,
                    2000,
                ][$index % 5],

               'field_type_id' => (($index % 3) + 1),

                'industry_type_id' => (($index % 4) + 1),

                'uploaded_doc_1' => null,
                'uploaded_doc_2' => null,
                'uploaded_doc_3' => null,

                'last_comment' => match ($workflowStatus) {
                    'rejected' =>
                        'Please provide valid supporting documents.',
                    'approved' =>
                        'Transaction approved by admin.',
                    'under_review' =>
                        'Transaction is currently under review.',
                    default => null,
                },

                'uploaded_doc_links' => null,

                'status' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create audit log
            |--------------------------------------------------------------------------
            */

            $transaction->logs()->create([
                'action' => 'created',
                'old_status' => null,
                'new_status' => 'draft',
                'actor_type' => 'frontend',

                'user_id' => $fieldAgent->id,

                'user_type' => 'FieldAgent',

                'comment' =>
                    'Sample event transaction created by Field Agent.',

                'old_values' => null,

                'new_values' => [
                    'workflow_status' => 'draft',
                ],

                'ip_address' => '127.0.0.1',

                'user_agent' => 'Seeder',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Add workflow log
            |--------------------------------------------------------------------------
            */

            if ($workflowStatus !== 'draft') {

                $transaction->logs()->create([
                    'action' => $workflowStatus,
                    'old_status' => 'draft',
                    'new_status' => $workflowStatus,
                    'actor_type' => in_array(
                        $workflowStatus,
                        [
                            'submitted',
                            'resubmitted',
                        ]
                    )
                        ? 'frontend'
                        : 'backend',

                    'user_id' => $fieldAgent->id,

                    'user_type' => 'FieldAgent',

                    'comment' =>
                        'Sample workflow status generated by seeder.',

                    'old_values' => [
                        'workflow_status' => 'draft',
                    ],

                    'new_values' => [
                        'workflow_status' => $workflowStatus,
                    ],

                    'ip_address' => '127.0.0.1',

                    'user_agent' => 'Seeder',
                ]);
            }

            $this->command->info(
                "Created Event Transaction #{$transaction->id} - {$workflowStatus}"
            );
        }

        $this->command->info(
            'Event transaction sample data created successfully.'
        );
    }
}