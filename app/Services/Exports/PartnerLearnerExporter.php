<?php

namespace App\Services\Exports;

use App\Models\Export;
use App\Models\Learner;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PartnerLearnerExporter
{
    public function export(Export $export): array
    {
        $requester = $export->requestedBy;

        if (!$requester instanceof Partner) {
            throw new RuntimeException(
                'Partner learner export requires a Partner requester.'
            );
        }

        if (
            $export->scope_type !== 'partner' ||
            (int) $export->scope_id !== (int) $requester->id
        ) {
            throw new RuntimeException(
                'Export scope does not match the requesting Partner.'
            );
        }

        // Always restrict this export to the authenticated Partner's scope.
        $query = Learner::query()
            ->where('learners.partner_id', '=', $requester->id);

        // Match Field Agents to learners using CSC/unit.
        $query->leftJoin('field_agents as export_fa', function ($join) use ($requester) {
            $join->on('export_fa.csc_id', '=', 'learners.unit')
                ->where('export_fa.partner_id', '=', $requester->id);
        });

        $this->applyFilters($query, $export->filters ?? []);

        $fileName = sprintf(
            'partner_learners_%d_%s_%d.csv',
            $requester->id,
            now()->format('Ymd_His'),
            $export->id
        );

        $directory = 'exports/partner-learners';

        Storage::disk('local')->makeDirectory($directory);

        $filePath = $directory . '/' . $fileName;
        $absolutePath = Storage::disk('local')->path($filePath);

        $handle = fopen($absolutePath, 'w');

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to create Partner learner export file.'
            );
        }

        $totalRecords = 0;

        try {
            // UTF-8 BOM for Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Learner ID',
                'First Name',
                'Last Name',
                'Gender',
                'Mobile',
                'Email',
                'Program Code',
                'Partner ID',
                'Partner',
                'Partner Center',
                'Field Agent',
                'Sakhi ID',
                'Unit',
                'City',
                'State',
                'Digital Proficiency',
                'English Proficiency',
                'Course Status',
                'Status',
                'Registration Date',
            ]);

            $query->select([
                'learners.id',
                'learners.partner_id',
                'learners.first_name',
                'learners.last_name',
                'learners.gender',
                'learners.normalized_mobile',
                'learners.phone_number',
                'learners.email',
                'learners.program_code',
                'learners.partner_center_id',
                'learners.unit',
                'learners.city',
                'learners.state',
                'learners.digital_proficiency_level',
                'learners.english_proficiency_level',
                'learners.course_completed',
                'learners.status',
                'learners.created_at',
                'export_fa.name as export_field_agent_name',
                'export_fa.sakhi_id as export_sakhi_id',
            ]);

            $query->with([
                'partner:id,name',
                'partnerCenter:id,name',
            ]);

            $query->distinct();

            logger()->error('PARTNER EXPORT QUERY CHECK', [
                'export_id' => $export->id,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $query->orderBy('learners.id')->chunkById(
                1000,
                function ($learners) use ($handle, &$totalRecords) {
                    foreach ($learners as $learner) {
                        fputcsv($handle, [
                            $learner->id,
                            $this->csvValue($learner->first_name),
                            $this->csvValue($learner->last_name),
                            $this->csvValue($learner->gender),
                            $this->csvValue(
                                $learner->normalized_mobile
                                    ?: $learner->phone_number
                            ),
                            $this->csvValue($learner->email),
                            $this->csvValue($learner->program_code),
                            $learner->partner_id,
                            $this->csvValue($learner->partner?->name),
                            $this->csvValue($learner->partnerCenter?->name),
                            $this->csvValue($learner->export_field_agent_name),
                            $this->csvValue($learner->export_sakhi_id),
                            $this->csvValue($learner->unit),
                            $this->csvValue($learner->city),
                            $this->csvValue($learner->state),
                            $this->csvValue(
                                $learner->digital_proficiency_level
                            ),
                            $this->csvValue(
                                $learner->english_proficiency_level
                            ),
                            $learner->course_completed
                                ? 'Completed'
                                : 'Pending',
                            $this->csvValue($learner->status),
                            optional($learner->created_at)
                                ->format('Y-m-d H:i:s'),
                        ]);

                        $totalRecords++;
                    }

                    fflush($handle);
                },
                'learners.id',
                'id'
            );

            fclose($handle);
        } catch (Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            Storage::disk('local')->delete($filePath);

            throw $e;
        }

        return [
            'file_name' => $fileName,
            'file_path' => $filePath,
            'total_records' => $totalRecords,
        ];
    }

    protected function applyFilters(
        Builder $query,
        array $filters
    ): void {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like) {
                $q->where('learners.first_name', 'LIKE', $like)
                    ->orWhere('learners.last_name', 'LIKE', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', learners.first_name, learners.last_name) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS('-', CONCAT_WS(' ', learners.first_name, learners.last_name), learners.phone_number) LIKE ?",
                        [$like]
                    )
                    ->orWhere('learners.phone_number', 'LIKE', $like)
                    ->orWhere('learners.normalized_mobile', 'LIKE', $like)
                    ->orWhere('learners.email', 'LIKE', $like)
                    ->orWhere('learners.city', 'LIKE', $like)
                    ->orWhere('learners.state', 'LIKE', $like)
                    ->orWhere('learners.unit', 'LIKE', $like)
                    ->orWhere(
                        'learners.digital_proficiency_level',
                        'LIKE',
                        $like
                    )
                    ->orWhere(
                        'learners.english_proficiency_level',
                        'LIKE',
                        $like
                    )
                    ->orWhere('export_fa.name', 'LIKE', $like)
                    ->orWhere('export_fa.sakhi_id', 'LIKE', $like);
            });
        }

        $name = trim((string) ($filters['name'] ?? ''));

        if ($name !== '') {
            $like = '%' . $name . '%';

            $query->where(function ($q) use ($like) {
                $q->where('learners.first_name', 'LIKE', $like)
                    ->orWhere('learners.last_name', 'LIKE', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', learners.first_name, learners.last_name) LIKE ?",
                        [$like]
                    );
            });
        }

        $columns = [
            'mobile' => 'learners.phone_number',
            'email' => 'learners.email',
            'unit' => 'learners.unit',
            'state' => 'learners.state',
            'city' => 'learners.city',
            'digital' => 'learners.digital_proficiency_level',
            'english' => 'learners.english_proficiency_level',
        ];

        foreach ($columns as $key => $column) {
            $value = trim((string) ($filters[$key] ?? ''));

            if ($value !== '') {
                $query->where(
                    $column,
                    'LIKE',
                    '%' . $value . '%'
                );
            }
        }

        $status = trim((string) ($filters['status'] ?? ''));

        if ($status !== '' && $status !== 'all') {
            $query->where('learners.status', '=', $status);
        }

        $agent = trim((string) ($filters['agent'] ?? ''));

        if ($agent !== '' && $agent !== 'all') {
            $query->where('export_fa.name', '=', $agent);
        }
    }

    protected function csvValue(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        if (
            $value !== '' &&
            preg_match('/^[=+\-@\t\r]/', $value)
        ) {
            return "'" . $value;
        }

        return $value;
    }
}
