<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAnnouncement;
use App\Models\AdminAnnouncementRecipient;
use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerPlacementUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminAnnouncementController extends Controller
{
    /**
     * Return the recipient model mapping for each announcement audience.
     */
    private function audienceModels(): array
    {
        return [
            'field_agents' => [
                'field_agent' => FieldAgent::class,
            ],
            'partners' => [
                'partner' => Partner::class,
            ],
            'placement_partners' => [
                'placement_partner' => PartnerPlacementUser::class,
            ],
            'all_modules' => [
                'field_agent' => FieldAgent::class,
                'partner' => Partner::class,
                'placement_partner' => PartnerPlacementUser::class,
            ],
        ];
    }

    /**
     * Resolve the permitted recipient model classes for an audience.
     */
    private function getAudienceClasses(string $audienceType): array
    {
        return array_values(
            $this->audienceModels()[$audienceType] ?? []
        );
    }

    /**
     * Return the model class associated with a recipient type.
     */
    private function getRecipientModel(string $type): ?string
    {
        foreach ($this->audienceModels() as $models) {
            if (isset($models[$type])) {
                return $models[$type];
            }
        }

        return null;
    }

    /**
     * Return the recipient type associated with a model class or morph alias.
     */
    private function getRecipientType(string $modelClass): ?string
    {
        $resolvedClass = Relation::getMorphedModel($modelClass)
            ?? $modelClass;

        foreach ($this->audienceModels() as $models) {
            foreach ($models as $type => $class) {
                if ($class === $resolvedClass) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Validate announcement fields and normalize selected recipients.
     */
    private function validateAnnouncement(
        Request $request,
        bool $requireAction = false
    ): array {
        $expiryRules = ['nullable', 'date'];

        // New announcements must have a future expiry date when one is supplied.
        if ($requireAction) {
            $expiryRules[] = 'after:now';
        }

        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'priority' => [
                'required',
                Rule::in(['normal', 'important', 'urgent']),
            ],
            'audience_type' => [
                'required',
                Rule::in([
                    'field_agents',
                    'partners',
                    'placement_partners',
                    'all_modules',
                ]),
            ],
            'target_type' => [
                'required',
                Rule::in(['all', 'selected']),
            ],
            'recipients' => [
                'required_if:target_type,selected',
                'array',
                'min:1',
            ],
            'recipients.*.type' => [
                'required_with:recipients',
                'string',
                Rule::in([
                    'field_agent',
                    'partner',
                    'placement_partner',
                ]),
            ],
            'recipients.*.id' => [
                'required_with:recipients',
                'integer',
                'min:1',
            ],
            'expires_at' => $expiryRules,
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],
            'remove_attachment' => ['nullable', 'boolean'],
        ];

        if ($requireAction) {
            $rules['action'] = [
                'required',
                Rule::in(['draft', 'publish']),
            ];
        }

        $data = $request->validate($rules);

        $data['recipients'] = $this->normalizeRecipients(
            $data['audience_type'],
            $data['target_type'],
            $data['recipients'] ?? []
        );

        return $data;
    }

    /**
     * Validate recipient module types, IDs and audience compatibility.
     */
    private function normalizeRecipients(
        string $audienceType,
        string $targetType,
        array $recipients
    ): array {
        if ($targetType === 'all') {
            return [];
        }

        $allowedTypes = array_keys(
            $this->audienceModels()[$audienceType] ?? []
        );

        $normalized = [];
        $uniqueKeys = [];

        foreach ($recipients as $recipient) {
            $type = $recipient['type'] ?? '';
            $id = (int) ($recipient['id'] ?? 0);

            if (!in_array($type, $allowedTypes, true)) {
                throw ValidationException::withMessages([
                    'recipients' => [
                        'A selected recipient does not belong to the chosen audience.',
                    ],
                ]);
            }

            $modelClass = $this->getRecipientModel($type);

            if (!$modelClass || $id < 1) {
                throw ValidationException::withMessages([
                    'recipients' => [
                        'One or more selected recipients are invalid.',
                    ],
                ]);
            }

            $uniqueKey = $type . ':' . $id;

            if (isset($uniqueKeys[$uniqueKey])) {
                throw ValidationException::withMessages([
                    'recipients' => [
                        'Duplicate recipients are not allowed.',
                    ],
                ]);
            }

            $uniqueKeys[$uniqueKey] = true;

            if (!$modelClass::query()->whereKey($id)->exists()) {
                throw ValidationException::withMessages([
                    'recipients' => [
                        'One or more selected recipients do not exist.',
                    ],
                ]);
            }

            $normalized[] = [
                'type' => $type,
                'id' => $id,
                'model' => $modelClass,
            ];
        }

        if (empty($normalized)) {
            throw ValidationException::withMessages([
                'recipients' => [
                    'Please select at least one recipient.',
                ],
            ]);
        }

        return $normalized;
    }

    /**
     * Return announcement listing, statistics and supported filters.
     */
    public function index(Request $request): JsonResponse
    {
        $base = AdminAnnouncement::query();

        $stats = [
            'total' => (clone $base)->count(),

            'published' => (clone $base)
                ->where('status', 'published')
                ->where(function (Builder $query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count(),

            'draft' => (clone $base)
                ->where('status', 'draft')
                ->count(),

            'disabled' => (clone $base)
                ->where('status', 'disabled')
                ->count(),

            'expired' => (clone $base)
                ->where('status', 'published')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count(),
        ];

        $query = clone $base;

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Apply the selected announcement status filter.
        $status = $request->input('status');

        if ($status === 'draft') {
            $query->where('status', 'draft');
        } elseif ($status === 'disabled') {
            $query->where('status', 'disabled');
        } elseif ($status === 'expired') {
            $query->where('status', 'published')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now());
        } elseif ($status === 'published') {
            $query->where('status', 'published')
                ->where(function (Builder $builder) {
                    $builder->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        }

        if (in_array($request->input('priority'), [
            'normal',
            'important',
            'urgent',
        ], true)) {
            $query->where('priority', $request->input('priority'));
        }

        if (in_array($request->input('audience_type'), [
            'field_agents',
            'partners',
            'placement_partners',
            'all_modules',
        ], true)) {
            $query->where(
                'audience_type',
                $request->input('audience_type')
            );
        }

        $announcements = $query
            ->withCount('recipients')
            ->withCount([
                'recipients as read_count' => function ($recipientQuery) {
                    $recipientQuery->whereHas('read');
                },
            ])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'announcements' => $announcements,
        ]);
    }

    /**
     * Return searchable recipient options for the selected audience.
     */
    public function recipientOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience_type' => [
                'required',
                Rule::in([
                    'field_agents',
                    'partners',
                    'placement_partners',
                    'all_modules',
                ]),
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $audienceType = $validated['audience_type'];
        $search = trim($validated['search'] ?? '');
        $results = [];

        foreach (
            $this->audienceModels()[$audienceType]
            as $type => $modelClass
        ) {
            $query = $modelClass::query();

            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query
                ->orderBy('name')
                ->limit(50)
                ->get();

            $results[$type] = $users->map(function (Model $user) use ($type) {
                return [
                    'id' => $user->getKey(),
                    'type' => $type,
                    'name' => $this->recipientDisplayName($user, $type),
                    'email' => $user->email,
                    'code' => match ($type) {
                        'field_agent' => $user->csc_id,
                        'partner' => $user->partner_code,
                        'placement_partner' => $user->pp_code,
                        default => null,
                    },
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'recipients' => $results,
        ]);
    }

    /**
     * Return the display name for a recipient model.
     */
    private function recipientDisplayName(Model $user, string $type): string
    {
        if ($type === 'placement_partner') {
            return $user->partner_placement_name ?: $user->name;
        }

        return $user->name;
    }

    /**
     * Create a draft announcement or publish it immediately.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateAnnouncement($request, true);
        $admin = $request->user('admin');
        $path = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')
                ->store('admin-announcements', 'local');
        }

        try {
            $announcement = DB::transaction(function () use (
                $admin,
                $data,
                $request,
                $path
            ) {
                $publishNow = $data['action'] === 'publish';

                $announcement = AdminAnnouncement::create([
                    'admin_id' => $admin->id,
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'priority' => $data['priority'],
                    'audience_type' => $data['audience_type'],
                    'target_type' => $data['target_type'],
                    'status' => $publishNow ? 'published' : 'draft',
                    'published_at' => $publishNow ? now() : null,
                    'expires_at' => $data['expires_at'] ?? null,
                    'attachment_path' => $path,
                    'attachment_name' => $request->hasFile('attachment')
                        ? $request->file('attachment')->getClientOriginalName()
                        : null,
                ]);

                if (
                    $data['target_type'] === 'selected' ||
                    $publishNow
                ) {
                    $this->syncRecipients(
                        $announcement,
                        $data['recipients'],
                        $publishNow
                    );
                }

                return $announcement;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => $announcement->status === 'published'
                ? 'Announcement published successfully.'
                : 'Announcement saved as draft.',
            'announcement' => $announcement->loadCount('recipients'),
        ], 201);
    }

    /**
     * Return announcement details with recipient and read information.
     */
    public function show(int $id): JsonResponse
    {
        $announcement = AdminAnnouncement::query()
            ->with(['recipients.read', 'recipients.recipient'])
            ->withCount('recipients')
            ->withCount([
                'recipients as read_count' => function ($query) {
                    $query->whereHas('read');
                },
            ])
            ->findOrFail($id);

        $recipients = $announcement->recipients->map(function (
            AdminAnnouncementRecipient $recipient
        ) {
            $user = $recipient->recipient;

            $type = $this->getRecipientType(
                $recipient->recipient_type
            ) ?? $recipient->recipient_type;

            return [
                'id' => $recipient->recipient_id,
                'type' => $type,
                'name' => $user
                    ? $this->recipientDisplayName($user, $type)
                    : 'Unavailable recipient',
                'email' => $user?->email,
                'is_read' => $recipient->read !== null,
                'read_at' => $recipient->read?->read_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'announcement' => [
                'id' => $announcement->id,
                'admin_id' => $announcement->admin_id,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'priority' => $announcement->priority,
                'audience_type' => $announcement->audience_type,
                'target_type' => $announcement->target_type,
                'status' => $announcement->status,
                'attachment_name' => $announcement->attachment_name,
                'published_at' => $announcement->published_at,
                'expires_at' => $announcement->expires_at,
                'created_at' => $announcement->created_at,
                'recipients_count' => $announcement->recipients_count,
                'read_count' => $announcement->read_count,
                'recipients' => $recipients,
            ],
        ]);
    }

    /**
     * Update a draft, published, disabled or expired announcement.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validateAnnouncement($request);
        $newPath = null;
        $oldPath = null;

        if ($request->hasFile('attachment')) {
            $newPath = $request->file('attachment')
                ->store('admin-announcements', 'local');
        }

        try {
            $announcement = DB::transaction(function () use (
                $id,
                $data,
                $request,
                $newPath,
                &$oldPath
            ) {
                $announcement = AdminAnnouncement::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                // Only these statuses are editable; soft-deleted records are excluded.
                abort_unless(
                    in_array($announcement->status, [
                        'draft',
                        'published',
                        'disabled',
                    ], true),
                    409,
                    'This announcement cannot be edited.'
                );

                $oldPath = $announcement->attachment_path;

                $values = [
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'priority' => $data['priority'],
                    'audience_type' => $data['audience_type'],
                    'target_type' => $data['target_type'],
                    'expires_at' => $data['expires_at'] ?? null,
                ];

                if ($newPath) {
                    $values['attachment_path'] = $newPath;
                    $values['attachment_name'] =
                        $request->file('attachment')->getClientOriginalName();
                } elseif ($request->boolean('remove_attachment')) {
                    $values['attachment_path'] = null;
                    $values['attachment_name'] = null;
                }

                // Keep the existing status and published_at unchanged.
                $announcement->update($values);

                // Synchronize recipients without deleting unchanged read history.
                $this->syncRecipients(
                    $announcement,
                    $data['recipients'],
                    $announcement->status !== 'draft'
                );

                return $announcement->fresh();
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        $shouldDeleteOldAttachment = (
            $oldPath &&
            (
                $newPath ||
                $request->boolean('remove_attachment')
            ) &&
            $oldPath !== $newPath
        );

        if ($shouldDeleteOldAttachment) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully.',
            'announcement' => $announcement->loadCount('recipients'),
        ]);
    }

    /**
     * Publish a draft using its saved recipient selection or current audience.
     */
    public function publish(int $id): JsonResponse
    {
        $announcement = DB::transaction(function () use ($id) {
            $announcement = AdminAnnouncement::query()
                ->lockForUpdate()
                ->findOrFail($id);

            abort_unless(
                $announcement->status === 'draft',
                409,
                'Only draft announcements can be published.'
            );

            if (
                $announcement->expires_at &&
                $announcement->expires_at->isPast()
            ) {
                throw ValidationException::withMessages([
                    'expires_at' => [
                        'Please update the expiry date before publishing.',
                    ],
                ]);
            }

            $selectedRecipients = [];

            if ($announcement->target_type === 'selected') {
                foreach ($announcement->recipients()->get() as $recipient) {
                    $type = $this->getRecipientType(
                        $recipient->recipient_type
                    );

                    if (!$type) {
                        throw ValidationException::withMessages([
                            'recipients' => [
                                'A saved recipient has an unsupported module type.',
                            ],
                        ]);
                    }

                    $selectedRecipients[] = [
                        'type' => $type,
                        'id' => $recipient->recipient_id,
                    ];
                }

                if (empty($selectedRecipients)) {
                    throw ValidationException::withMessages([
                        'recipients' => [
                            'Please select at least one recipient before publishing.',
                        ],
                    ]);
                }

                $selectedRecipients = $this->normalizeRecipients(
                    $announcement->audience_type,
                    'selected',
                    $selectedRecipients
                );
            }

            $this->syncRecipients(
                $announcement,
                $selectedRecipients,
                true
            );

            $announcement->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            return $announcement->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Announcement published successfully.',
            'announcement' => $announcement->loadCount('recipients'),
        ]);
    }

    /**
     * Disable an existing announcement.
     */
    public function disable(int $id)
    {
        $announcement = AdminAnnouncement::findOrFail($id);

        $announcement->status = 'disabled';
        $announcement->save();

        return response()->json([
            'success' => true,
            'message' => 'Announcement disabled successfully.',
            'data' => $announcement,
        ]);
    }

    /**
     * Enable a previously disabled announcement.
     */
    public function enable(int $id)
    {
        $announcement = AdminAnnouncement::findOrFail($id);

        if ($announcement->status !== 'disabled') {
            return response()->json([
                'success' => false,
                'message' => 'Only disabled announcements can be enabled.',
            ], 422);
        }

        $announcement->status = 'published';
        $announcement->save();

        return response()->json([
            'success' => true,
            'message' => 'Announcement enabled successfully.',
            'data' => $announcement,
        ]);
    }



    /**
     * Synchronize recipient snapshots while preserving unchanged read records.
     */
    private function syncRecipients(
        AdminAnnouncement $announcement,
        array $selectedRecipients,
        bool $publishing = false
    ): void {
        // Keep All audience drafts unsnapshotted until publication.
        if (
            $announcement->target_type === 'all' &&
            $announcement->status === 'draft' &&
            !$publishing
        ) {
            $announcement->recipients()->delete();

            return;
        }

        $desiredRecipients = [];

        if ($announcement->target_type === 'all') {
            foreach (
                $this->getAudienceClasses($announcement->audience_type)
                as $modelClass
            ) {
                $type = $this->getRecipientType($modelClass);

                foreach (
                    $modelClass::query()->select('id')->cursor()
                    as $user
                ) {
                    $morphType = Relation::getMorphAlias($modelClass);
                    $key = $morphType . ':' . $user->getKey();

                    $desiredRecipients[$key] = [
                        'recipient_type' => $morphType,
                        'recipient_id' => $user->getKey(),
                    ];
                }
            }
        } else {
            foreach ($selectedRecipients as $recipient) {
                $morphType = Relation::getMorphAlias(
                    $recipient['model']
                );

                $key = $morphType . ':' . $recipient['id'];

                $desiredRecipients[$key] = [
                    'recipient_type' => $morphType,
                    'recipient_id' => $recipient['id'],
                ];
            }
        }

        if (
            empty($desiredRecipients) &&
            ($publishing || $announcement->status !== 'draft')
        ) {
            throw ValidationException::withMessages([
                'recipients' => [
                    'No eligible recipients found for this announcement.',
                ],
            ]);
        }

        $existingRecipients = $announcement->recipients()->get();

        $existingByKey = [];

        foreach ($existingRecipients as $recipient) {
            $key = $recipient->recipient_type
                . ':'
                . $recipient->recipient_id;

            $existingByKey[$key] = $recipient;
        }

        $desiredKeys = array_fill_keys(
            array_keys($desiredRecipients),
            true
        );

        // Delete only recipients removed from the new selection.
        $removedIds = [];

        foreach ($existingByKey as $key => $recipient) {
            if (!isset($desiredKeys[$key])) {
                $removedIds[] = $recipient->id;
            }
        }

        if (!empty($removedIds)) {
            $announcement->recipients()
                ->whereIn('id', $removedIds)
                ->delete();
        }

        // Insert only newly added recipients; existing read rows remain intact.
        $newRows = [];
        $now = now();

        foreach ($desiredRecipients as $key => $recipient) {
            if (isset($existingByKey[$key])) {
                continue;
            }

            $newRows[] = [
                'announcement_id' => $announcement->id,
                'recipient_type' => $recipient['recipient_type'],
                'recipient_id' => $recipient['recipient_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($newRows, 500) as $chunk) {
            DB::table('admin_announcement_recipients')->insert($chunk);
        }
    }

    /**
     * Soft-delete an announcement and remove its private attachment.
     */
    public function destroy(int $id): JsonResponse
    {
        $announcement = AdminAnnouncement::query()->findOrFail($id);
        $attachmentPath = $announcement->attachment_path;

        $announcement->delete();

        if ($attachmentPath) {
            Storage::disk('local')->delete($attachmentPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.',
        ]);
    }

    /**
     * Download an announcement attachment from private local storage.
     */
    public function attachment(int $id)
    {
        $announcement = AdminAnnouncement::query()->findOrFail($id);

        abort_unless(
            $announcement->attachment_path &&
            Storage::disk('local')->exists(
                $announcement->attachment_path
            ),
            404,
            'Attachment not found.'
        );

        return Storage::disk('local')->download(
            $announcement->attachment_path,
            $announcement->attachment_name
                ?: basename($announcement->attachment_path)
        );
    }
}
