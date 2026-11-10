<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use App\Models\AdminAnnouncementRecipient;
use App\Models\AdminAnnouncementRead;
use App\Models\FieldAgent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminAnnouncementController extends Controller
{
    /**
     * Build a query for Admin announcements assigned to the authenticated agent.
     */
    private function assignedAnnouncements(Request $request): Builder
    {
        $agent = $request->user('field_agent');

        // Resolve the exact polymorphic type used when Admin recipients were saved.
        $recipientType = Relation::getMorphAlias(FieldAgent::class);

        return AdminAnnouncementRecipient::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $agent->id)
            ->whereHas('announcement', function ($query) {
                $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where(function ($expiry) {
                        $expiry->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            });
    }

    /**
     * Return published Admin announcements assigned to the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:all,read,unread'],
            'priority' => ['nullable', 'in:normal,important,urgent'],
        ]);

        $query = $this->assignedAnnouncements($request)
            ->with([
                'announcement',
                'read',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('announcement', function ($announcement) use ($search) {
                $announcement->where(function ($filter) use ($search) {
                    $filter->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('priority')) {
            $priority = $request->priority;

            $query->whereHas('announcement', function ($announcement) use ($priority) {
                $announcement->where('priority', $priority);
            });
        }

        if ($request->status === 'read') {
            $query->whereHas('read');
        }

        if ($request->status === 'unread') {
            $query->whereDoesntHave('read');
        }

        $announcements = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'announcements' => $announcements,
        ]);
    }

    /**
     * Return the number of currently visible unread Admin announcements.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->assignedAnnouncements($request)
            ->whereDoesntHave('read')
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Return details of an Admin announcement assigned to the authenticated agent.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $recipient = $this->assignedAnnouncements($request)
            ->where('announcement_id', $id)
            ->with(['announcement', 'read'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'announcement' => $recipient->announcement,
            'is_read' => $recipient->read !== null,
            'read_at' => $recipient->read?->read_at,
        ]);
    }

    /**
     * Mark an assigned Admin announcement as read without creating duplicates.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $recipient = $this->assignedAnnouncements($request)
            ->where('announcement_id', $id)
            ->firstOrFail();

        // The unique recipient_id constraint prevents duplicate read records.
        DB::table('admin_announcement_reads')->insertOrIgnore([
            'recipient_id' => $recipient->id,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $read = AdminAnnouncementRead::query()
            ->where('recipient_id', $recipient->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Admin announcement marked as read.',
            'is_read' => true,
            'read_at' => $read->read_at,
        ]);
    }

    /**
     * Securely preview an Admin announcement attachment assigned to the agent.
     */
    public function attachment(Request $request, int $id)
    {
        $recipient = $this->assignedAnnouncements($request)
            ->where('announcement_id', $id)
            ->with('announcement')
            ->firstOrFail();

        $announcement = $recipient->announcement;

        abort_unless(
            $announcement->attachment_path &&
            Storage::disk('local')->exists($announcement->attachment_path),
            404,
            'Attachment not found.'
        );

        // Allow inline preview only for supported attachment types.
        $extension = strtolower(
            pathinfo($announcement->attachment_path, PATHINFO_EXTENSION)
        );

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ];

        abort_unless(isset($mimeTypes[$extension]), 415);

        return response()->file(
            Storage::disk('local')->path($announcement->attachment_path),
            [
                'Content-Type' => $mimeTypes[$extension],
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }
}
