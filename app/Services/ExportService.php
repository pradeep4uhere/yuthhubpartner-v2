<?php

namespace App\Services;

use App\Models\Export;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use App\Jobs\ProcessExportJob;

class ExportService
{
    /**
     * Create a central export request.
     */
    public function create(Model $requester, array $data): Export
    {
        $this->validate($data);

        $export =  $requester->exports()->create([
            'type' => $data['type'],
            'scope_type' => $data['scope_type'] ?? null,
            'scope_id' => $data['scope_id'] ?? null,
            'title' => $data['title'],
            'filters' => $data['filters'] ?? null,
            'status' => 'queued',
            'total_records' => 0,
        ]);

        ProcessExportJob::dispatch($export->id);

        return $export;
    }

    /**
     * Validate export request.
     */
    protected function validate(array $data): void
    {
        $allowedTypes = [
            'placement_partners',
            'partners',
            'partner_centers',
            'field_agents',
            'learners',
            'events',
            'transactions',
            'reports',
        ];

        $allowedScopes = [
            'system',
            'placement_partner',
            'partner',
            'field_agent',
        ];

        if (empty($data['type'])) {
            throw new InvalidArgumentException(
                'Export type is required.'
            );
        }

        if (!in_array($data['type'], $allowedTypes, true)) {
            throw new InvalidArgumentException(
                'Invalid export type: ' . $data['type']
            );
        }

        if (empty($data['title'])) {
            throw new InvalidArgumentException(
                'Export title is required.'
            );
        }

        if (
            isset($data['scope_type']) &&
            $data['scope_type'] !== null &&
            !in_array($data['scope_type'], $allowedScopes, true)
        ) {
            throw new InvalidArgumentException(
                'Invalid export scope: ' . $data['scope_type']
            );
        }

        if (
            isset($data['scope_type']) &&
            $data['scope_type'] !== 'system' &&
            empty($data['scope_id'])
        ) {
            throw new InvalidArgumentException(
                'Scope ID is required for non-system exports.'
            );
        }
    }
}
