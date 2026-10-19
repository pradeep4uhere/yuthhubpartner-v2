<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Export event activities to an Excel workbook.
 *
 * Exports all records matching the authorized and filtered query.
 */
class EventActivitiesExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    /**
     * The authorized event activity query.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * Initialize the export with the filtered query.
     *
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Return the query used to retrieve event activities.
     *
     * @return Builder
     */
    public function query(): Builder
    {
        return $this->query->orderBy('id');
    }

    /**
     * Define the Excel column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Activity ID',
            'Learner ID',
            'Beneficiary Name',
            'Phone Number',
            'Event Type',
            'Event Category',
            'Event Date',
            'Event Value',
            'Review Status',
            'Workflow Status',
            'Submission Date',
        ];
    }

    /**
     * Map an event transaction to an Excel row.
     *
     * @param mixed $activity
     *
     * @return array<int, mixed>
     */
    public function map($activity): array
    {
        return [
            $activity->id,
            $activity->learner_id,
            $activity->beneficiary_name,
            $activity->beneficiary_phone_number,
            $activity->eventMasterType?->name ?? '',
            $activity->eventMaster?->event_name ?? '',
            $activity->event_create_date,
            $activity->event_value,
            $activity->review_status,
            $activity->workflow_status,
            $activity->event_date_submitted,
        ];
    }
}
