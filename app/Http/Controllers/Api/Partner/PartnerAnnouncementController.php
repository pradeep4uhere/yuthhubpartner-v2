<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\FieldAgent;
use App\Models\PartnerAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PartnerAnnouncementController extends Controller
{
    /**
     * Return the authenticated partner's announcement listing and statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        $base = PartnerAnnouncement::query()
            ->where('partner_id', $partner->id);

        $stats = [
            'total' => (clone $base)->count(),
            'published' => (clone $base)
                ->where('status', 'published')->count(),
            'draft' => (clone $base)
                ->where('status', 'draft')->count(),
            'expired' => (clone $base)
                ->where('status', 'published')
                ->where('expires_at', '<=', now())
                ->count(),
        ];

        $query = clone $base;

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->status === 'expired') {
            $query->where('status', 'published')
                ->where('expires_at', '<=', now());
        } elseif (in_array($request->status, ['draft', 'published'], true)) {
            $query->where('status', $request->status);
        }

        if (in_array($request->priority, [
            'normal', 'important', 'urgent'
        ], true)) {
            $query->where('priority', $request->priority);
        }

        $announcements = $query
            ->withCount(['recipients', 'reads'])
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
     * Return only Field Agents belonging to the authenticated partner.
     */
    public function fieldAgents(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        $agents = FieldAgent::query()
            ->where('partner_id', $partner->id)
            ->select(['id', 'name', 'csc_id'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'field_agents' => $agents,
        ]);
    }

    /**
     * Validate announcement fields and selected recipient IDs.
     */
    private function validateAnnouncement(Request $request, int $partnerId): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'priority' => [
                'required',
                Rule::in(['normal', 'important', 'urgent']),
            ],
            'target_type' => [
                'required',
                Rule::in(['all', 'selected']),
            ],
            'field_agent_ids' => [
                'required_if:target_type,selected',
                'array',
                'min:1',
            ],
            'field_agent_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('field_agents', 'id')
                    ->where('partner_id', $partnerId),
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        return $data;
    }

    /**
     * 9
     * Create a draft or immediately publish an announcement.
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        $data = $this->validateAnnouncement($request, $partner->id);

        $action = $request->validate([
            'action' => [
                'required',
                Rule::in(['draft', 'publish']),
            ],
        ]);

        $path = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')
                ->store('partner-announcements', 'local');
        }

        try {
            $announcement = DB::transaction(function () use (
                $partner, $data, $action, $path, $request
            ) {
                $announcement = PartnerAnnouncement::create([
                    'partner_id' => $partner->id,
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'priority' => $data['priority'],
                    'target_type' => $data['target_type'],
                    'status' => $action['action'] === 'publish'
                        ? 'published'
                        : 'draft',
                    'expires_at' => $data['expires_at'] ?? null,
                    'published_at' => $action['action'] === 'publish'
                        ? now()
                        : null,
                    'attachment_path' => $path,
                    'attachment_name' => $request->hasFile('attachment')
                        ? $request->file('attachment')->getClientOriginalName()
                        : null,
                ]);

                // Drafts retain their selected audience; published announcements
                // receive a fixed recipient snapshot.
                if ($announcement->target_type === 'selected' ||
                    $announcement->status === 'published') {
                    $this->syncRecipients(
                        $announcement,
                        $partner->id,
                        $data['field_agent_ids'] ?? []
                    );
                }

                return $announcement;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
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
     * Return one announcement belonging to the authenticated partner.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $announcement = PartnerAnnouncement::query()
            ->where('partner_id', $request->user('partner')->id)
            ->with([
                'recipients.fieldAgent:id,name,csc_id',
                'recipients.read',
            ])
            ->withCount(['recipients', 'reads'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'announcement' => $announcement,
        ]);
    }

    /**
     * Update an unpublished draft and its selected recipients.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $partner = $request->user('partner');

        $announcement = PartnerAnnouncement::query()
            ->where('partner_id', $partner->id)
            ->findOrFail($id);

        abort_unless($announcement->status === 'draft', 409,
            'Published announcements cannot be edited.');

        $data = $this->validateAnnouncement($request, $partner->id);

        $oldPath = $announcement->attachment_path;
        $newPath = null;

        if ($request->hasFile('attachment')) {
            $newPath = $request->file('attachment')
                ->store('partner-announcements', 'local');
        }

        try {
            DB::transaction(function () use (
                $announcement, $partner, $data, $newPath, $request
            ) {
                $values = [
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'priority' => $data['priority'],
                    'target_type' => $data['target_type'],
                    'expires_at' => $data['expires_at'] ?? null,
                ];

                if ($newPath) {
                    $values['attachment_path'] = $newPath;
                    $values['attachment_name'] =
                        $request->file('attachment')->getClientOriginalName();
                }

                $announcement->update($values);

                $this->syncRecipients(
                    $announcement,
                    $partner->id,
                    $data['field_agent_ids'] ?? []
                );
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $e;
        }

        if ($newPath && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Draft updated successfully.',
            'announcement' => $announcement->fresh()->loadCount('recipients'),
        ]);
    }

    /**
     * Publish a draft using its saved audience selection.
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $partner = $request->user('partner');

        $announcement = DB::transaction(function () use ($partner, $id) {
            $announcement = PartnerAnnouncement::query()
                ->where('partner_id', $partner->id)
                ->lockForUpdate()
                ->findOrFail($id);

            abort_unless($announcement->status === 'draft', 409,
                'Announcement is already published.');

            if ($announcement->expires_at &&
                $announcement->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'expires_at' => 'Please update the expiry date before publishing.',
                ]);
            }

            $selectedIds = $announcement->target_type === 'selected'
                ? $announcement->recipients()->pluck('field_agent_id')->all()
                : [];

            if ($announcement->target_type === 'selected' && !$selectedIds) {
                throw ValidationException::withMessages([
                    'field_agent_ids' => 'Please select at least one Field Agent.',
                ]);
            }

            $this->syncRecipients(
                $announcement,
                $partner->id,
                $selectedIds
            );

            $announcement->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            return $announcement;
        });

        return response()->json([
            'success' => true,
            'message' => 'Announcement published successfully.',
            'announcement' => $announcement->loadCount('recipients'),
        ]);
    }

    /**
     * Soft-delete an announcement owned by the authenticated partner.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $announcement = PartnerAnnouncement::query()
            ->where('partner_id', $request->user('partner')->id)
            ->findOrFail($id);

        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.',
        ]);
    }

    /**
     * Download an announcement attachment after verifying partner ownership.
     */
    public function attachment(Request $request, int $id)
    {
        $announcement = PartnerAnnouncement::query()
            ->where('partner_id', $request->user('partner')->id)
            ->findOrFail($id);

        abort_unless(
            $announcement->attachment_path &&
            Storage::disk('local')->exists($announcement->attachment_path),
            404,
            'Attachment not found.'
        );

        return Storage::disk('local')->download(
            $announcement->attachment_path,
            $announcement->attachment_name
                ?: basename($announcement->attachment_path)
        );
    }


    /**
     * Synchronize announcement recipients for all or selected Field Agents.
     */
    private function syncRecipients(
        PartnerAnnouncement $announcement,
        int $partnerId,
        array $selectedIds = []
    ): void {
        // Resolve the recipient IDs for the announcement audience.
        if ($announcement->target_type === 'all') {
            $ids = FieldAgent::query()
                ->where('partner_id', $partnerId)
                ->pluck('id')
                ->all();
        } else {
            $ids = array_values(array_unique(array_map(
                'intval',
                $selectedIds
            )));
        }

        // Restrict recipients to Field Agents belonging to this partner.
        $validIds = [];

        foreach (array_chunk($ids, 500) as $idChunk) {
            $chunkIds = DB::table('field_agents')
                ->where('partner_id', $partnerId)
                ->whereIn('id', $idChunk)
                ->pluck('id')
                ->all();

            $validIds = array_merge($validIds, $chunkIds);
        }

        //dd(['partner_id' => $partnerId, 'target_type' => $announcement->target_type, 'ids_count' => count($ids), 'valid_ids_count' => count($validIds), 'announcement_id' => $announcement->id]);

        // Reject invalid selections rather than silently excluding agents.
        if (
            $announcement->target_type === 'selected' &&
            count($validIds) !== count($ids)
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'field_agent_ids' => 'One or more selected Field Agents are invalid.',
            ]);
        }

        // Replace the recipient snapshot inside the caller's transaction.
        $announcement->recipients()->delete();

        if (empty($validIds)) {
            if ($announcement->status === 'published') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'field_agent_ids' => 'No eligible Field Agents found for this announcement. Please verify the partner Field Agent assignments.',
                ]);
            }

            return;
        }

        $now = now();

        $rows = array_map(
            fn ($id) => [
                'announcement_id' => $announcement->id,
                'field_agent_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $validIds
        );

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('partner_announcement_recipients')->insert($chunk);
        }
    }


}
