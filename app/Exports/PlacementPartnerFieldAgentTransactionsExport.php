<?php

namespace App\Exports;

use App\Models\EventTransaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PlacementPartnerFieldAgentTransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Builder $query;

    /**
     * Initialize the export with the fully filtered transaction query.
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * Return every matching transaction from the database without UI pagination.
     */
    public function query(): Builder
    {
        return $this->query
            ->with(['learner', 'fieldType', 'industryType'])
            ->orderByDesc('id');
    }

    /**
     * Define the Excel column headings centrally in the backend.
     */
    public function headings(): array
    {
        return [
            'Transaction ID',
            'Agent ID',
            'Unit / Institute',
            'Event Name',
            'Event Category',
            'Beneficiary Name',
            'Beneficiary Number',
            'Event Status',
            'Event Submitted',
            'Document 1 URL',
            'Document 2 URL',
            'Document 3 URL',
            'Differently Abled',
            'Education Level',
            'Course Name',
            'Marital Status',
            'Religion',
            'Digital Proficiency Level',
            'English Proficiency Level',
            'Interested Opportunities',
            'Function Type',
            'Industry Type',
            'Age Group',
            'State',
            'District',
            'Comment',
            'Last Updated',
        ];
    }

    /**
     * Map one database transaction to the backend-defined Excel columns.
     */
    public function map($tx): array
    {
        $learner = $tx->learner;
        $agent = $tx->fieldAgent;

        return [
            $tx->id,
            $agent?->csc_id ?: $agent?->sakhi_id,
            $learner?->unit,
            $tx->event_type_name,
            $tx->event_category_name,
            $tx->beneficiary_name,
            $tx->beneficiary_phone_number,
            $tx->review_status ?: $tx->workflow_status,
            $tx->event_date_submitted,
            $this->documentUrl($tx->uploaded_doc_1),
            $this->documentUrl($tx->uploaded_doc_2),
            $this->documentUrl($tx->uploaded_doc_3),
            $learner?->differently_abled,
            $learner?->education_level,
            $learner?->course_name,
            $learner?->marital_status,
            $learner?->religion,
            $learner?->digital_proficiency_level,
            $learner?->english_proficiency_level,
            $learner?->interested_opportunities,
            $tx->fieldType?->name,
            $tx->industryType?->name,
            $learner?->age_group,
            $learner?->state ?: $agent?->state,
            $learner?->district ?: $agent?->district,
            $tx->last_comment,
            $tx->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Return a public URL for an uploaded document when its file exists.
     */
    protected function documentUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($path)
            : null;
    }
}
