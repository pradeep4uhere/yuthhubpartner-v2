<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\AdminAnnouncementRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminAnnouncementController extends Controller
{
    /**
     * Get published Admin announcements assigned to the authenticated Partner.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        $recipients = $partner->adminAnnouncementRecipients()
            ->with(['announcement', 'read'])
            ->whereHas('announcement', function ($query): void {
                $query->where('status', 'published')
                    ->where(function ($expiryQuery): void {
                        $expiryQuery->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
            })
            ->whereHas('announcement')
            ->latest('id')
            ->paginate(10);

        $recipients->getCollection()->transform(function ($recipient): array {
            $announcement = $recipient->announcement;

            return [
                'id' => $announcement->id,
                'recipient_id' => $recipient->id,
                'title' => $announcement->title,
                'message' => $announcement->message,
                'priority' => $announcement->priority,
                'published_at' => $announcement->published_at,
                'expires_at' => $announcement->expires_at,
                'is_read' => (bool) $recipient->read,
                'read_at' => $recipient->read?->read_at,
                'has_attachment' => !empty($announcement->attachment_path),
                'attachment_name' => $announcement->attachment_name,
                'attachment_url' => !empty($announcement->attachment_path)
                    ? url("/api/partner/admin-announcements/{$announcement->id}/attachment")
                    : null,
            ];
        });

        return response()->json($recipients);
    }

    /**
     * Return the unread Admin announcement count for the authenticated Partner.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        $count = $partner->adminAnnouncementRecipients()
            ->whereDoesntHave('read')
            ->whereHas('announcement', function ($query): void {
                $query->where('status', 'published')
                    ->where(function ($expiryQuery): void {
                        $expiryQuery->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
            })
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark an assigned Admin announcement as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $partner = $request->user('partner');

        $recipient = $partner->adminAnnouncementRecipients()
            ->whereHas('announcement', function ($query): void {
                $query->where('status', 'published')
                    ->where(function ($expiryQuery): void {
                        $expiryQuery->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
            })
            ->whereHas('announcement', function ($query) use ($id): void {
                $query->whereKey($id);
            })
            ->firstOrFail();

        $recipient->read()->firstOrCreate(
            ['recipient_id' => $recipient->id],
            ['read_at' => now()]
        );

        return response()->json([
            'message' => 'Announcement marked as read.',
            'is_read' => true,
        ]);
    }

    /**
     * Preview an attachment only when it belongs to an announcement assigned to this Partner.
     */
    public function attachment(Request $request, int $id): BinaryFileResponse
    {
        $partner = $request->user('partner');

        $recipient = $partner->adminAnnouncementRecipients()
            ->whereHas('announcement', function ($query) use ($id): void {
                $query->whereKey($id)
                    ->where('status', 'published')
                    ->where(function ($expiryQuery): void {
                        $expiryQuery->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
            })
            ->with('announcement')
            ->firstOrFail();

        $announcement = $recipient->announcement;

        abort_unless(
            $announcement->attachment_path
                && Storage::disk('local')->exists($announcement->attachment_path),
            404,
            'Attachment not found.'
        );

        $path = Storage::disk('local')->path($announcement->attachment_path);
        $mimeType = Storage::disk('local')->mimeType($announcement->attachment_path);

        abort_unless(
            in_array($mimeType, [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ], true),
            415,
            'This attachment type cannot be previewed.'
        );

        $fileName = $announcement->attachment_name
            ?: basename($announcement->attachment_path);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }
}
