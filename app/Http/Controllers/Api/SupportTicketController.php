<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketReply;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SupportTicketController extends Controller
{
    /**
     * Create a support ticket for the authenticated dashboard user.
     *
     * The requester is derived from the authenticated session and its
     * supportTickets relationship. Requester IDs and assignment details
     * supplied by the client are never accepted as ticket ownership data.
     *
     * A temporary unique ticket number is replaced inside the transaction
     * with a readable number based on the inserted ticket's unique ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('support_ticket_categories', 'id')
                    ->where('is_active', true),
            ],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:20000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv',
                'max:5120',
            ],
            'affected_module' => ['nullable', 'string', 'max:100'],
            'priority' => [
                'sometimes',
                'string',
                Rule::in(['low', 'normal', 'high', 'urgent']),
            ],
        ]);


        $user = $this->supportUser($request);

        if (! $user || ! method_exists($user, 'supportTickets')) {
            return response()->json([
                'message' => 'Authenticated dashboard user is not supported.',
            ], 401);
        }

        $ticket = DB::transaction(function () use ($user, $validated) {
            $ticket = $user->supportTickets()->create([
                'ticket_number' => 'TMP-' . Str::upper(Str::random(24)),
                'category_id' => $validated['category_id'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'affected_module' => $validated['affected_module'] ?? null,
                'priority' => $validated['priority'] ?? 'normal',
                'status' => 'open',
            ]);

            $ticket->update([
                'ticket_number' => sprintf(
                    'TKT-%s-%06d',
                    $ticket->created_at->format('Y'),
                    $ticket->id
                ),
            ]);

            return $ticket->fresh(['category', 'requester']);
        });

        return response()->json([
            'message' => 'Support ticket created successfully.',
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'affected_module' => $ticket->affected_module,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'category' => [
                    'id' => $ticket->category->id,
                    'name' => $ticket->category->name,
                ],
                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }















    /**
 * List support tickets belonging to the authenticated dashboard user.
 *
 * Tickets are retrieved only through the authenticated user's
 * supportTickets relationship. Results are paginated to avoid returning
 * an unnecessarily large list in one response.
 *
 * @param Request $request
 * @return JsonResponse
 */
public function index(Request $request): JsonResponse
{
     $user = $this->supportUser($request);


    if (! $user || ! method_exists($user, 'supportTickets')) {
        return response()->json([
            'message' => 'Authenticated dashboard user is not supported.',
        ], 401);
    }

    $tickets = $user->supportTickets()
        ->with('category','attachments')
        ->latest('support_tickets.created_at')
        ->paginate(15);

    $data = $tickets->getCollection()->map(
        function (SupportTicket $ticket): array {
            return [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'category' => [
                    'id' => $ticket->category?->id,
                    'name' => $ticket->category?->name,
                ],
                'last_reply_at' => $ticket->last_reply_at,
                'created_at' => $ticket->created_at,
            ];
        }
    )->values();

    return response()->json([
        'data' => $data,
        'meta' => [
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage(),
            'per_page' => $tickets->perPage(),
            'total' => $tickets->total(),
        ],
    ]);
}


/**
 * Display a support ticket with its category, ticket attachments,
 * and public conversation replies including their attachments.
 *
 * The ticket is scoped to the authenticated user's support tickets.
 * Internal notes are excluded, and attachment download URLs are
 * generated for the frontend to display uploaded files.
 *
 * @param Request $request
 * @param int $ticketId
 * @return JsonResponse
 */
public function show(Request $request, int $ticketId): JsonResponse
{
     $user = $this->supportUser($request);


    if (! $user || ! method_exists($user, 'supportTickets')) {
        return response()->json([
            'message' => 'Authenticated dashboard user is not supported.',
        ], 401);
    }

    $ticket = $user->supportTickets()
        ->with(['category', 'attachments'])
        ->whereKey($ticketId)
        ->firstOrFail();

    /*
     * Format ticket-level attachments for the frontend.
     */
    $formatAttachments = function ($attachments) use ($request): array {

        return $attachments->map(function ($attachment) use ($request): array {

            return [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => (int) $attachment->file_size,

                'download_url' => $this->attachmentDownloadUrl(
                    $request,
                    $attachment->id
                ),
            ];

        })->values()->all();
    };

    /*
     * Retrieve public replies and load their attachments.
     */
    $replies = $ticket->replies()
        ->with(['sender', 'attachments'])
        ->where('is_internal_note', false)
        ->oldest('created_at')
        ->get()
        ->map(function ($reply) use ($user, $formatAttachments): array {
            $sentByCurrentUser =
                $reply->sender_type === get_class($user)
                && (string) $reply->sender_id === (string) $user->getKey();

            return [
                'id' => $reply->id,
                'message' => $reply->message,
                'sender' => $sentByCurrentUser ? 'You' : 'Support Team',
                'created_at' => $reply->created_at,
                'attachments' => $formatAttachments($reply->attachments),
            ];
        })
        ->values();

    return response()->json([
        'data' => [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'affected_module' => $ticket->affected_module,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => [
                'id' => $ticket->category?->id,
                'name' => $ticket->category?->name,
            ],
            'created_at' => $ticket->created_at,
            'last_reply_at' => $ticket->last_reply_at,

            // Attachments uploaded when creating the ticket.
            'attachments' => $formatAttachments($ticket->attachments),

            // Replies include their own uploaded attachments.
            'replies' => $replies,
        ],
    ]);
}



/**
 * Add a public reply to a ticket owned by the authenticated user.
 *
 * The ticket is retrieved through the user's supportTickets relationship,
 * so users cannot reply to tickets belonging to another account. Public
 * dashboard users cannot create internal notes through this endpoint.
 *
 * A reply reopens a resolved ticket. Closed tickets reject new replies.
 * The ticket's last_reply_at timestamp is updated in the same transaction.
 *
 * @param Request $request
 * @param int $ticketId
 * @return JsonResponse
 */

/**
 * Store a reply on an existing support ticket.
 *
 * Validates the reply message and attachments, verifies that the
 * authenticated user owns the ticket, and prevents replies to closed
 * tickets. Attachments are stored within the database transaction flow.
 *
 * Resolved tickets are reopened when a new reply is added.
 *
 * @param Request $request The incoming HTTP request.
 * @param int $ticketId The ID of the support ticket.
 * @return JsonResponse The result of the reply creation operation.
 */
public function storeReply(Request $request, int $ticketId): JsonResponse
{
    $validated = $request->validate([
        'message' => [
            'nullable',
            'string',
            'max:20000',
            'required_without:attachments',
        ],
        'attachments' => ['sometimes', 'array', 'max:5'],
        'attachments.*' => [
            'file',
            'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv',
            'max:5120',
        ],
    ]);

     $user = $this->supportUser($request);


    if (! $user || ! method_exists($user, 'supportTickets')) {
        return response()->json([
            'message' => 'Authenticated dashboard user is not supported.',
        ], 401);
    }

    $reply = DB::transaction(function () use (
        $request,
        $user,
        $ticketId,
        $validated
    ) {
        $ticket = $user->supportTickets()
            ->whereKey($ticketId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($ticket->status === 'closed') {
            return null;
        }

        $reply = new \App\Models\SupportTicketReply([
            'message' => $validated['message'] ?? null,
            'is_internal_note' => false,
        ]);

        $reply->ticket()->associate($ticket);
        $reply->sender()->associate($user);
        $reply->save();

        $this->storeReplyAttachments(
            $request,
            $ticket,
            $reply,
            $user
        );

        $ticket->last_reply_at = $reply->created_at ?? now();

        if ($ticket->status === 'resolved') {
            $ticket->status = 'open';
            $ticket->resolved_at = null;
        }

        $ticket->save();

        return $reply;
    });

    if (! $reply) {
        return response()->json([
            'message' => 'This ticket is closed and cannot receive new replies.',
        ], 422);
    }

    return response()->json([
        'message' => 'Reply added successfully.',
        'data' => [
            'id' => $reply->id,
            'support_ticket_id' => $reply->support_ticket_id,
            'message' => $reply->message,
            'is_internal_note' => $reply->is_internal_note,
            'sender' => 'You',
            'created_at' => $reply->created_at,
        ],
    ], 201);
}



/**
 * Return active support ticket categories for the authenticated dashboard user.
 *
 * Only active categories are returned, ordered by their configured sort order.
 * This endpoint supplies the category dropdown used when creating a ticket.
 *
 * @param Request $request
 * @return JsonResponse
 */
public function categories(Request $request): JsonResponse
{
    $user = $this->supportUser($request);


    if (! $user || ! method_exists($user, 'supportTickets')) {
        return response()->json([
            'message' => 'Authenticated dashboard user is not supported.',
        ], 401);
    }

    $categories = \App\Models\SupportTicketCategory::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'description']);

    return response()->json([
        'data' => $categories,
    ]);
}





/**
 * Store uploaded files against a ticket and, optionally, a reply.
 *
 * The caller must validate the attachments array before calling this
 * method. Files are stored on Laravel's private local disk, and the
 * uploader is derived from the authenticated user.
 *
 * @param Request $request
 * @param SupportTicket $ticket
 * @param SupportTicketReply|null $reply
 * @return void
 */
private function storeAttachments(
    Request $request,
    SupportTicket $ticket,
    ?SupportTicketReply $reply = null
): void {
    $files = $request->file('attachments', []);

    foreach ($files as $file) {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            continue;
        }

        $path = $file->store('support-ticket-attachments', 'local');

        $attachment = new SupportTicketAttachment([
            'original_name' => mb_substr(
                basename($file->getClientOriginalName()),
                0,
                255
            ),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        $attachment->ticket()->associate($ticket);

        if ($reply) {
            $attachment->reply()->associate($reply);
        }

        $attachment->uploadedBy()->associate($request->user());
        $attachment->save();
    }
}

    /**
     * Format attachment metadata for the authenticated dashboard.
     *
     * No filesystem path is exposed to the client. Downloads must use the
     * authenticated download endpoint.
     *
     * @param SupportTicketAttachment $attachment
     * @return array<string, mixed>
     */
    private function formatAttachment(
        SupportTicketAttachment $attachment
    ): array {
        return [
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'download_url' => url(
                '/api/field-agent/support-ticket-attachments/'
                . $attachment->id
                . '/download'
            ),
            'created_at' => $attachment->created_at,
        ];
    }



    /**
     * Download a support ticket attachment after verifying ticket ownership.
     *
     * Retrieves the stored file path from the file_path column, verifies that
     * the attachment belongs to a ticket owned by the authenticated user, and
     * returns the file as a download response.
     *
     * @param Request $request
     * @param int $attachmentId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    */
    public function downloadAttachment(
        Request $request,
        int $attachmentId
    ) {
         $user = $this->supportUser($request);


        if (! $user || ! method_exists($user, 'supportTickets')) {
            return response()->json([
                'message' => 'Unauthenticated or unsupported dashboard user.',
            ], 401);
        }

        $attachment = SupportTicketAttachment::query()
            ->whereKey($attachmentId)
            ->firstOrFail();

        $ownsTicket = $user->supportTickets()
            ->whereKey($attachment->support_ticket_id)
            ->exists();

        abort_unless($ownsTicket, 403);

        // Read the same database column used when storing the attachment.
        $path = $attachment->file_path;

        abort_if(
            ! is_string($path) || trim($path) === '',
            404,
            'Attachment file path is missing.'
        );

        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($path),
            404,
            'Attachment file not found on storage.'
        );

        return $disk->download(
            $path,
            $attachment->original_name
        );
    }





    /**
 * Store uploaded files against the newly created support ticket reply.
 *
 * Files are stored on Laravel's private local disk. Each attachment
 * record is linked to both the parent ticket and the reply.
 *
 * @param \Illuminate\Http\Request $request
 * @param \App\Models\SupportTicket $ticket
 * @param \App\Models\SupportTicketReply $reply
 * @param mixed $user
 * @return void
 */


/**
 * Store attachments associated with a support ticket reply.
 *
 * Saves uploaded files to the local storage disk and creates
 * attachment records linked to the ticket, reply, and uploader.
 *
 * The uploader is stored through the uploaded_by polymorphic
 * relationship using uploaded_by_type and uploaded_by_id.
 *
 * @param Request $request The incoming HTTP request.
 * @param SupportTicket $ticket The support ticket receiving the reply.
 * @param SupportTicketReply $reply The newly created ticket reply.
 * @param \Illuminate\Database\Eloquent\Model $user The authenticated uploader.
 * @return void
 */
private function storeReplyAttachments(
    Request $request,
    SupportTicket $ticket,
    SupportTicketReply $reply,
    $user
): void {
    foreach ($request->file('attachments', []) as $file) {
        $path = $file->store(
            'support-tickets/' . $ticket->id,
            'local'
        );

        if ($path === false) {
            throw new \RuntimeException(
                'Unable to store the support ticket attachment.'
            );
        }

        $attachment = new SupportTicketAttachment([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $attachment->ticket()->associate($ticket);
        $attachment->reply()->associate($reply);
        $attachment->uploaded_by()->associate($user);
        $attachment->save();
    }
}



    /**
     * Resolve authenticated support requester.
     */
    private function supportUser(Request $request)
    {
        foreach (['partner', 'field_agent'] as $guard) {

            $user = $request->user($guard);

            if ($user && method_exists($user, 'supportTickets')) {
                return $user;
            }
        }

        return null;
    }



    private function attachmentDownloadUrl(
        Request $request,
        int $attachmentId
    ): string {

        $routeName = $request->routeIs('api.partner.*')
            ? 'api.partner.support-ticket-attachments.download'
            : 'api.field-agent.support-ticket-attachments.download';

        return route($routeName, [
            'attachmentId' => $attachmentId,
        ]);
    }

}
