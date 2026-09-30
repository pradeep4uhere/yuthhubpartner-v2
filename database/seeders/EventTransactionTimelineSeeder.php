<?php

namespace Database\Seeders;

use App\Models\EventTransaction;
use App\Models\EventTransactionLog;
use App\Models\FieldAgent;
use App\Models\Learner;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventTransactionTimelineSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $learners = Learner::query()
                ->where('status', 'active')
                ->get();

            $fieldAgents = FieldAgent::query()
                ->where('status', 'active')
                ->get();

            if ($learners->isEmpty()) {
                $this->command->error('No active learners found.');
                return;
            }

            if ($fieldAgents->isEmpty()) {
                $this->command->error('No active field agents found.');
                return;
            }

            $partners = Partner::query()
                ->where('status', 'active')
                ->get();

            $partnerCenters = PartnerCenter::query()
                ->where('status', 'active')
                ->get();

            $placementUsers = PartnerPlacementUser::query()
                ->where('status', 'active')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Create 50+ Event Transactions
            |--------------------------------------------------------------------------
            */

            $cases = [

                // -------------------------------------------------------------
                // 1 - 8 : DRAFT
                // -------------------------------------------------------------

                [
                    'status' => 'draft',
                    'count' => 8,
                ],

                // -------------------------------------------------------------
                // 9 - 16 : SUBMITTED
                // -------------------------------------------------------------

                [
                    'status' => 'submitted',
                    'count' => 8,
                ],

                // -------------------------------------------------------------
                // 17 - 24 : UNDER REVIEW
                // -------------------------------------------------------------

                [
                    'status' => 'under_review',
                    'count' => 8,
                ],

                // -------------------------------------------------------------
                // 25 - 32 : REJECTED
                // -------------------------------------------------------------

                [
                    'status' => 'rejected',
                    'count' => 8,
                ],

                // -------------------------------------------------------------
                // 33 - 38 : RESUBMITTED
                // -------------------------------------------------------------

                [
                    'status' => 'resubmitted',
                    'count' => 6,
                ],

                // -------------------------------------------------------------
                // 39 - 46 : APPROVED
                // -------------------------------------------------------------

                [
                    'status' => 'approved',
                    'count' => 8,
                ],

                // -------------------------------------------------------------
                // 47 - 50 : MULTIPLE REJECTION / RESUBMISSION
                // -------------------------------------------------------------

                [
                    'status' => 'approved',
                    'count' => 4,
                    'multiple_cycle' => true,
                ],
            ];

            $counter = 0;

            foreach ($cases as $case) {

                for ($i = 0; $i < $case['count']; $i++) {

                    $counter++;

                    $learner = $learners->get(($counter - 1) % $learners->count());
                    $fieldAgent = $fieldAgents->get(($counter - 1) % $fieldAgents->count());

                    $partner = $partners->isNotEmpty()
                        ? $partners->get(($counter - 1) % $partners->count())
                        : null;

                    $partnerCenter = $partnerCenters->isNotEmpty()
                        ? $partnerCenters->get(($counter - 1) % $partnerCenters->count())
                        : null;

                    $placementUser = $placementUsers->isNotEmpty()
                        ? $placementUsers->get(($counter - 1) % $placementUsers->count())
                        : null;

                    $transaction = $this->createTransaction(
                        index: $counter,
                        status: $case['status'],
                        learner: $learner,
                        fieldAgent: $fieldAgent,
                        partner: $partner,
                        partnerCenter: $partnerCenter,
                        placementUser: $placementUser
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Timeline
                    |--------------------------------------------------------------------------
                    */

                    $baseDate = Carbon::create(
                        2026,
                        9,
                        1
                    )->addDays($counter);

                    $this->createLog(
                        transaction: $transaction,
                        action: 'created',
                        oldStatus: null,
                        newStatus: 'draft',
                        fieldAgent: $fieldAgent,
                        comment: 'Event transaction created.',
                        date: $baseDate->copy()->setTime(10, 0)
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Draft
                    |--------------------------------------------------------------------------
                    */

                    if ($case['status'] === 'draft') {

                        $transaction->update([
                            'workflow_status' => 'draft',
                            'review_status' => null,
                            'last_comment' => null,
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Submitted
                    |--------------------------------------------------------------------------
                    */

                    $submittedDate = $baseDate->copy()->addDay()->setTime(11, 15);

                    $this->createLog(
                        transaction: $transaction,
                        action: 'submitted',
                        oldStatus: 'draft',
                        newStatus: 'submitted',
                        fieldAgent: $fieldAgent,
                        comment: 'Event transaction submitted for review.',
                        date: $submittedDate
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Submitted only
                    |--------------------------------------------------------------------------
                    */

                    if ($case['status'] === 'submitted') {

                        $transaction->update([
                            'workflow_status' => 'submitted',
                            'review_status' => null,
                            'event_date_submitted' => $submittedDate->toDateString(),
                            'last_comment' => null,
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Under Review
                    |--------------------------------------------------------------------------
                    */

                    $reviewDate = $baseDate->copy()->addDays(2)->setTime(13, 30);

                    $this->createLog(
                        transaction: $transaction,
                        action: 'under_review',
                        oldStatus: 'submitted',
                        newStatus: 'under_review',
                        userType: 'Admin',
                        userId: 1,
                        comment: 'Event transaction is under review.',
                        date: $reviewDate
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Under Review only
                    |--------------------------------------------------------------------------
                    */

                    if ($case['status'] === 'under_review') {

                        $transaction->update([
                            'workflow_status' => 'under_review',
                            'review_status' => 'under_review',
                            'event_date_submitted' => $submittedDate->toDateString(),
                            'last_comment' => 'Event transaction is under review.',
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Rejected
                    |--------------------------------------------------------------------------
                    */

                    $rejectDate = $baseDate->copy()->addDays(3)->setTime(14, 20);

                    $this->createLog(
                        transaction: $transaction,
                        action: 'rejected',
                        oldStatus: 'under_review',
                        newStatus: 'rejected',
                        userType: 'Admin',
                        userId: 1,
                        comment: $this->getRejectionComment($counter),
                        date: $rejectDate
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Rejected only
                    |--------------------------------------------------------------------------
                    */

                    if ($case['status'] === 'rejected') {

                        $transaction->update([
                            'workflow_status' => 'rejected',
                            'review_status' => 'rejected',
                            'event_date_submitted' => $submittedDate->toDateString(),
                            'last_comment' => $this->getRejectionComment($counter),
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Resubmitted
                    |--------------------------------------------------------------------------
                    */

                    $resubmitDate = $baseDate->copy()->addDays(4)->setTime(11, 10);

                    $this->createLog(
                        transaction: $transaction,
                        action: 'resubmitted',
                        oldStatus: 'rejected',
                        newStatus: 'resubmitted',
                        fieldAgent: $fieldAgent,
                        comment: 'Transaction resubmitted after making the required corrections.',
                        date: $resubmitDate
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Resubmitted only
                    |--------------------------------------------------------------------------
                    */

                    if ($case['status'] === 'resubmitted') {

                        $transaction->update([
                            'workflow_status' => 'resubmitted',
                            'review_status' => 'resubmitted',
                            'event_date_submitted' => $resubmitDate->toDateString(),
                            'last_comment' => 'Transaction resubmitted after making the required corrections.',
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Multiple rejection / resubmission cycle
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($case['multiple_cycle'])) {

                        $rejectDate2 = $baseDate->copy()->addDays(5)->setTime(15, 0);

                        $this->createLog(
                            transaction: $transaction,
                            action: 'rejected',
                            oldStatus: 'resubmitted',
                            newStatus: 'rejected',
                            userType: 'Admin',
                            userId: 1,
                            comment: 'Additional document clarification is required.',
                            date: $rejectDate2
                        );

                        $resubmitDate2 = $baseDate->copy()->addDays(6)->setTime(10, 45);

                        $this->createLog(
                            transaction: $transaction,
                            action: 'resubmitted',
                            oldStatus: 'rejected',
                            newStatus: 'resubmitted',
                            fieldAgent: $fieldAgent,
                            comment: 'Additional document clarification submitted.',
                            date: $resubmitDate2
                        );

                        $approveDate = $baseDate->copy()->addDays(7)->setTime(16, 30);

                        $this->createLog(
                            transaction: $transaction,
                            action: 'approved',
                            oldStatus: 'resubmitted',
                            newStatus: 'approved',
                            userType: 'Admin',
                            userId: 1,
                            comment: 'Event transaction approved after final verification.',
                            date: $approveDate
                        );

                        $transaction->update([
                            'workflow_status' => 'approved',
                            'review_status' => 'approved',
                            'event_date_submitted' => $resubmitDate2->toDateString(),
                            'last_comment' => 'Event transaction approved after final verification.',
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Approved
                    |--------------------------------------------------------------------------
                    */

                    $approveDate = $baseDate->copy()->addDays(5)->setTime(16, 30);

                    $this->createLog(
                        transaction: $transaction,
                        action: 'approved',
                        oldStatus: 'resubmitted',
                        newStatus: 'approved',
                        userType: 'Admin',
                        userId: 1,
                        comment: 'Event transaction approved.',
                        date: $approveDate
                    );

                    $transaction->update([
                        'workflow_status' => 'approved',
                        'review_status' => 'approved',
                        'event_date_submitted' => $resubmitDate->toDateString(),
                        'last_comment' => 'Event transaction approved.',
                    ]);
                }
            }

            $this->command->info(
                "Event Transaction Timeline Seeder completed. Created {$counter} transactions."
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Create Transaction
    |--------------------------------------------------------------------------
    */

    private function createTransaction(
        int $index,
        string $status,
        Learner $learner,
        FieldAgent $fieldAgent,
        ?Partner $partner,
        ?PartnerCenter $partnerCenter,
        ?PartnerPlacementUser $placementUser
    ): EventTransaction {

        return EventTransaction::create([

            'learner_id' => $learner->id,

            'partner_id' => $partner?->id,

            'partner_center_id' => $partnerCenter?->id,

            'partner_placement_user_id' => $placementUser?->id,

            'field_agent_id' => $fieldAgent->id,

            'review_status' => in_array(
                $status,
                ['under_review', 'rejected', 'resubmitted', 'approved']
            )
                ? $status
                : null,

            'workflow_status' => $status,

            'beneficiary_phone_number' =>
                $learner->normalized_mobile
                ?? $learner->phone_number
                ?? '999999' . str_pad($index, 4, '0', STR_PAD_LEFT),

            'beneficiary_name' =>
                $learner->name
                ?? 'Test Learner ' . $index,

            'event_master_id' => 'EM-' . str_pad($index, 4, '0', STR_PAD_LEFT),

            'event_master_type_id' => 'EMT-' . str_pad($index, 4, '0', STR_PAD_LEFT),

            'event_type_name' => 'Training Event',

            'event_category_name' => 'Skilling',

            'event_create_date' => Carbon::now()->subDays($index),

            'event_date_submitted' => null,

            'event_value' => 1000 + ($index * 100),

            'field_type_id' => 1,

            'industry_type_id' => 1,

            'uploaded_doc_1' => 'documents/test-document-' . $index . '-1.pdf',

            'uploaded_doc_2' => 'documents/test-document-' . $index . '-2.pdf',

            'uploaded_doc_3' => null,

            'last_comment' => null,

            'uploaded_doc_links' => null,

            'status' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Timeline Log
    |--------------------------------------------------------------------------
    */

    private function createLog(
        EventTransaction $transaction,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?FieldAgent $fieldAgent = null,
        ?string $userType = null,
        ?int $userId = null,
        ?string $comment = null,
        ?Carbon $date = null
    ): void {

        if ($fieldAgent) {
            $userType = 'FieldAgent';
            $userId = $fieldAgent->id;
        }

        EventTransactionLog::create([

            'event_transaction_id' => $transaction->id,

            'action' => $action,

            'old_status' => $oldStatus,

            'new_status' => $newStatus,

            'actor_type' =>
                $userType === 'Admin'
                    ? 'backend'
                    : 'frontend',

            'user_id' => $userId,

            'user_type' => $userType,

            'comment' => $comment,

            'old_values' => $oldStatus
                ? [
                    'workflow_status' => $oldStatus,
                ]
                : null,

            'new_values' => $newStatus
                ? [
                    'workflow_status' => $newStatus,
                ]
                : null,

            'ip_address' => '127.0.0.1',

            'user_agent' => 'Event Transaction Timeline Seeder',

            'created_at' => $date ?? now(),

            'updated_at' => $date ?? now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Rejection Comments
    |--------------------------------------------------------------------------
    */

    private function getRejectionComment(int $index): string
    {
        $comments = [

            'Please resubmit the document.',

            'Beneficiary document is not clear. Please upload a clear copy.',

            'Required information is missing. Please update and resubmit.',

            'Uploaded document does not match the beneficiary details.',

            'Please provide the correct supporting document.',

            'Document verification failed. Please submit a valid document.',

            'Event details require correction before approval.',

            'Please update the beneficiary information and resubmit.',
        ];

        return $comments[($index - 1) % count($comments)];
    }
}