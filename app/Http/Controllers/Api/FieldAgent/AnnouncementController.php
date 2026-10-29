<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use App\Models\PartnerAnnouncement;
use App\Models\PartnerAnnouncementRecipient;
use App\Models\PartnerAnnouncementRead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /**
     * Return announcements published specifically for the authenticated agent.
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user('field_agent');

        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:all,read,unread'],
            'priority' => ['nullable', 'in:normal,important,urgent'],
        ]);

        $query = PartnerAnnouncementRecipient::query()
            ->where('field_agent_id', $agent->id)
            ->whereHas('announcement', function ($q) use ($request) {
                $q->where('partner_id', $request->user('field_agent')->partner_id)
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where(function ($expiry) {
                        $expiry->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });

                if ($request->filled('search')) {
                    $search = trim($request->search);

                    $q->where(function ($filter) use ($search) {
                        $filter->where('title', 'like', "%{$search}%")
                            ->orWhere('message', 'like', "%{$search}%");
                    });
                }

                if ($request->filled('priority')) {
                    $q->where('priority', $request->priority);
                }
            })
            ->with([
                'announcement:id,partner_id,title,message,priority,target_type,status,published_at,expires_at,attachment_path,attachment_name',
                'read:id,recipient_id,read_at',
            ]);

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
     * Return the number of currently visible unread announcements.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $agent = $request->user('field_agent');

        $count = PartnerAnnouncementRecipient::query()
            ->where('field_agent_id', $agent->id)
            ->whereDoesntHave('read')
            ->whereHas('announcement', function ($q) use ($agent) {
                $q->where('partner_id', $agent->partner_id)
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where(function ($expiry) {
                        $expiry->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Return a visible announcement assigned to the authenticated agent.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $agent = $request->user('field_agent');

        $recipient = PartnerAnnouncementRecipient::query()
            ->where('field_agent_id', $agent->id)
            ->whereHas('announcement', function ($q) use ($id, $agent) {
                $q->whereKey($id)
                    ->where('partner_id', $agent->partner_id)
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where(function ($expiry) {
                        $expiry->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
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
     * Mark an assigned announcement as read without creating duplicates.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $agent = $request->user('field_agent');

        $recipient = PartnerAnnouncementRecipient::query()
            ->where('field_agent_id', $agent->id)
            ->whereHas('announcement', function ($q) use ($id, $agent) {
                $q->whereKey($id)
                    ->where('partner_id', $agent->partner_id)
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where(function ($expiry) {
                        $expiry->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->firstOrFail();

        // The unique recipient_id constraint prevents duplicate read records.
        DB::table('partner_announcement_reads')->insertOrIgnore([
            'recipient_id' => $recipient->id,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $read = PartnerAnnouncementRead::query()
            ->where('recipient_id', $recipient->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read.',
            'is_read' => true,
            'read_at' => $read->read_at,
        ]);
    }




    /**
 * Securely preview an announcement attachment assigned to the agent.
 */
public function attachment(Request $request, int $id)
{
    $agent = $request->user('field_agent');

    // Verify that this announcement belongs to the logged-in agent.
    $recipient = PartnerAnnouncementRecipient::query()
        ->where('field_agent_id', $agent->id)
        ->whereHas('announcement', function ($query) use ($id, $agent) {
            $query->whereKey($id)
                ->where('partner_id', $agent->partner_id)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where(function ($expiry) {
                    $expiry->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        })
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
    $extension = strtolower(pathinfo($announcement->attachment_path, PATHINFO_EXTENSION));

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
