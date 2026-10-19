<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\FieldAgent;
use App\Models\Learner;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use App\Models\EventTransaction;

class ExportScopeService
{
    /**
     * Get learner query allowed for requester.
     */
    public function learnerQuery(Model $requester): Builder
    {
        return match (true) {

            $requester instanceof Admin =>
                Learner::query(),

            $requester instanceof Partner =>
                Learner::query()
                    ->where('partner_id', $requester->id),

            $requester instanceof FieldAgent =>
                Learner::query()
                    ->where('field_agent_id', $requester->id),

            $requester instanceof PartnerPlacementUser =>
                Learner::query()
                    ->whereHas('fieldAgent', function (Builder $query) use ($requester) {
                        $query->where(
                            'placement_partner_user_id',
                            $requester->id
                        );
                    }),

            default =>
                throw $this->unsupportedRequester($requester),
        };
    }

    /**
     * Get field agent query allowed for requester.
     */
    public function fieldAgentQuery(Model $requester): Builder
    {
        return match (true) {

            $requester instanceof Admin =>
                FieldAgent::query(),

            $requester instanceof Partner =>
                FieldAgent::query()
                    ->where('partner_id', $requester->id),

            $requester instanceof FieldAgent =>
                FieldAgent::query()
                    ->where('id', $requester->id),

            $requester instanceof PartnerPlacementUser =>
                FieldAgent::query()
                    ->where(
                        'placement_partner_user_id',
                        $requester->id
                    ),

            default =>
                throw $this->unsupportedRequester($requester),
        };
    }

    /**
     * Get partner center query allowed for requester.
     */
    public function partnerCenterQuery(Model $requester): Builder
    {
        return match (true) {

            $requester instanceof Admin =>
                PartnerCenter::query(),

            $requester instanceof Partner =>
                PartnerCenter::query()
                    ->where('partner_id', $requester->id),

            $requester instanceof FieldAgent =>
                PartnerCenter::query()
                    ->where('id', $requester->partner_center_id),

            $requester instanceof PartnerPlacementUser =>
                PartnerCenter::query()
                    ->whereIn(
                        'id',
                        FieldAgent::query()
                            ->select('partner_center_id')
                            ->where(
                                'placement_partner_user_id',
                                $requester->id
                            )
                            ->whereNotNull('partner_center_id')
                    ),

            default =>
                throw $this->unsupportedRequester($requester),
        };
    }

    /**
     * Get partner query allowed for requester.
     */
    public function partnerQuery(Model $requester): Builder
    {
        return match (true) {

            $requester instanceof Admin =>
                Partner::query(),

            $requester instanceof Partner =>
                Partner::query()
                    ->where('id', $requester->id),

            $requester instanceof FieldAgent =>
                Partner::query()
                    ->where('id', $requester->partner_id),

            $requester instanceof PartnerPlacementUser =>
                Partner::query()
                    ->whereIn(
                        'id',
                        FieldAgent::query()
                            ->select('partner_id')
                            ->where(
                                'placement_partner_user_id',
                                $requester->id
                            )
                            ->whereNotNull('partner_id')
                    ),

            default =>
                throw $this->unsupportedRequester($requester),
        };
    }

    /**
     * Throw unsupported requester exception.
     */
    protected function unsupportedRequester(Model $requester): InvalidArgumentException
    {
        return new InvalidArgumentException(
            'Unsupported export requester: ' .
            get_class($requester)
        );
    }



    /**
     * Get event activity query allowed for the requester.
     *
     * Admins can access all event activities. Partners can access activities
     * associated with their field agents. Field agents can access only their
     * own activities. Placement users can access activities belonging to
     * their assigned field agents.
     *
     * @param Model $requester The authenticated requester.
     *
     * @return Builder
     *
     * @throws InvalidArgumentException When the requester is unsupported.
     */
    public function eventActivityQuery(Model $requester): Builder
    {
        return match (true) {
            $requester instanceof Admin =>
                EventTransaction::query(),

            $requester instanceof Partner =>
                EventTransaction::query()
                    ->whereIn(
                        'field_agent_id',
                        FieldAgent::query()
                            ->select('id')
                            ->where('partner_id', $requester->id)
                    ),

            $requester instanceof FieldAgent =>
                EventTransaction::query()
                    ->where('field_agent_id', $requester->id),

            $requester instanceof PartnerPlacementUser =>
                EventTransaction::query()
                    ->whereIn(
                        'field_agent_id',
                        FieldAgent::query()
                            ->select('id')
                            ->where(
                                'placement_partner_user_id',
                                $requester->id
                            )
                    ),

            default =>
                throw $this->unsupportedRequester($requester),
        };
    }





}
