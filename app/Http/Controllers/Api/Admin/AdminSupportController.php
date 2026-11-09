<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketCategory;
use App\Models\SupportTicketReply;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSupportController extends Controller
{
    /**
     * Return support dashboard statistics for the Admin workspace.
     */
    public function summary(): JsonResponse
    {
        $query = SupportTicket::query();

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'total' => (clone $query)->count(),
                'open' => (int) ($statusCounts['open'] ?? 0),
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'resolved' => (int) ($statusCounts['resolved'] ?? 0),
                'closed' => (int) ($statusCounts['closed'] ?? 0),
                'urgent' => (clone $query)->where('priority', 'urgent')->count(),
                'unassigned' => (clone $query)
                    ->whereNull('assigned_to_id')
                    ->count(),
            ],
        ]);
    }

    /**
     * Return a searchable and filterable list of support tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => [
                'nullable',
                Rule::in(['open', 'pending', 'resolved', 'closed']),
            ],
            'priority' => [
                'nullable',
                Rule::in(['low', 'normal', 'high', 'urgent']),
            ],
            'category_id' => ['nullable', 'integer', 'exists:support_ticket_categories,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:admins,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tickets = SupportTicket::query()
            ->with(['category', 'requester', 'assignedTo'])
            ->when($validated['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) =>
                $query->where('status', $status)
            )
            ->when($validated['priority'] ?? null, fn (Builder $query, string $priority) =>
                $query->where('priority', $priority)
            )
            ->when($validated['category_id'] ?? null, fn (Builder $query, int $categoryId) =>
                $query->where('category_id', $categoryId)
            )
            ->when(array_key_exists('assigned_to', $validated), function (Builder $query) use ($validated) {
                // Match tickets that have no assigned account.
                if ($validated['assigned_to'] === null) {
                    $query->whereNull('assigned_to_id')
                        ->whereNull('assigned_to_type');
                } else {
                    $query->where('assigned_to_id', $validated['assigned_to'])
                        ->where('assigned_to_type', (new Admin())->getMorphClass());
                }
            })
            ->latest('created_at')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'data' => $tickets->getCollection()
                ->map(fn (SupportTicket $ticket) => $this->formatTicket($ticket))
                ->values(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Return a ticket with its requester, assignment, attachments and full conversation.
     */
    public function show(int $ticketId): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->with([
                'category',
                'requester',
                'assignedTo',
                'attachments',
                'replies.sender',
                'replies.attachments',
            ])
            ->findOrFail($ticketId);

        return response()->json([
            'data' => array_merge(
                $this->formatTicket($ticket),
                [
                    'description' => $ticket->description,
                    'affected_module' => $ticket->affected_module,
                    'resolved_at' => $ticket->resolved_at,
                    'closed_at' => $ticket->closed_at,
                    'attachments' => $ticket->attachments
                        ->whereNull('support_ticket_reply_id')
                        ->map(fn (SupportTicketAttachment $attachment) =>
                            $this->formatAttachment($attachment)
                        )
                        ->values(),
                    'replies' => $ticket->replies
                        ->sortBy('created_at')
                        ->map(fn (SupportTicketReply $reply) => [
                            'id' => $reply->id,
                            'message' => $reply->message,
                            'is_internal_note' => (bool) $reply->is_internal_note,
                            'sender' => [
                                'name' => $reply->sender?->name ?? 'Support Team',
                                'type' => class_basename($reply->sender_type),
                            ],
                            'created_at' => $reply->created_at,
                            'attachments' => $reply->attachments
                                ->map(fn (SupportTicketAttachment $attachment) =>
                                    $this->formatAttachment($attachment)
                                )
                                ->values(),
                        ])
                        ->values(),
                ]
            ),
        ]);
    }

    /**
     * Add a public Admin reply to a support ticket.
     */
    public function reply(Request $request, int $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv',
                'max:5120',
            ],
        ]);

        $admin = $request->user('admin');
        $ticket = SupportTicket::query()->findOrFail($ticketId);

        // Prevent public replies on closed tickets.
        if (! $ticket->isOpenForReplies()) {
            return response()->json([
                'message' => 'This ticket is closed. Reopen it before adding a reply.',
            ], 422);
        }

        $reply = DB::transaction(function () use (
            $request,
            $validated,
            $admin,
            $ticket
        ) {
            $ticket = SupportTicket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $reply = new SupportTicketReply([
                'message' => $validated['message'] ?? null,
                'is_internal_note' => false,
            ]);

            $reply->ticket()->associate($ticket);
            $reply->sender()->associate($admin);
            $reply->save();

            $this->storeAdminAttachments($request, $ticket, $reply, $admin);

            $ticket->last_reply_at = $reply->created_at ?? now();

            if ($ticket->status === 'resolved') {
                $ticket->status = 'open';
                $ticket->resolved_at = null;
            }

            $ticket->save();

            return $reply->load(['sender', 'attachments']);
        });

        return response()->json([
            'message' => 'Reply added successfully.',
            'data' => [
                'id' => $reply->id,
                'message' => $reply->message,
                'is_internal_note' => false,
                'sender' => $admin->name,
                'created_at' => $reply->created_at,
                'attachments' => $reply->attachments
                    ->map(fn (SupportTicketAttachment $attachment) =>
                        $this->formatAttachment($attachment)
                    )
                    ->values(),
            ],
        ], 201);
    }

    /**
     * Add a private internal note that is not visible to the ticket requester.
     */
    public function internalNote(Request $request, int $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv',
                'max:5120',
            ],
        ]);

        $admin = $request->user('admin');
        $ticket = SupportTicket::query()->findOrFail($ticketId);

        $reply = DB::transaction(function () use (
            $request,
            $validated,
            $admin,
            $ticket
        ) {
            $reply = new SupportTicketReply([
                'message' => $validated['message'],
                'is_internal_note' => true,
            ]);

            $reply->ticket()->associate($ticket);
            $reply->sender()->associate($admin);
            $reply->save();

            $this->storeAdminAttachments($request, $ticket, $reply, $admin);

            return $reply->load(['sender', 'attachments']);
        });

        return response()->json([
            'message' => 'Internal note added successfully.',
            'data' => [
                'id' => $reply->id,
                'message' => $reply->message,
                'is_internal_note' => true,
                'sender' => $admin->name,
                'created_at' => $reply->created_at,
                'attachments' => $reply->attachments
                    ->map(fn (SupportTicketAttachment $attachment) =>
                        $this->formatAttachment($attachment)
                    )
                    ->values(),
            ],
        ], 201);
    }

    /**
     * Update ticket status and maintain its resolution or closure timestamps.
     */
    public function updateStatus(Request $request, int $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['open', 'pending', 'resolved', 'closed']),
            ],
        ]);

        $ticket = SupportTicket::query()->findOrFail($ticketId);
        $ticket->status = $validated['status'];

       // Maintain resolution and closure timestamps according to the selected status.
        if ($ticket->status === 'resolved') {
            $ticket->resolved_at = $ticket->resolved_at ?? now();
            $ticket->closed_at = null;
        } elseif ($ticket->status === 'closed') {
            $ticket->closed_at = $ticket->closed_at ?? now();
        } else {
            $ticket->resolved_at = null;
            $ticket->closed_at = null;
        }

        $ticket->save();

        return response()->json([
            'message' => 'Ticket status updated successfully.',
            'data' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'resolved_at' => $ticket->resolved_at,
                'closed_at' => $ticket->closed_at,
            ],
        ]);
    }

    /**
     * Update the priority of a support ticket.
     */
    public function updatePriority(Request $request, int $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'priority' => [
                'required',
                Rule::in(['low', 'normal', 'high', 'urgent']),
            ],
        ]);

        $ticket = SupportTicket::query()->findOrFail($ticketId);
        $ticket->update(['priority' => $validated['priority']]);

        return response()->json([
            'message' => 'Ticket priority updated successfully.',
            'data' => [
                'id' => $ticket->id,
                'priority' => $ticket->priority,
            ],
        ]);
    }


    /**
     * Assign or unassign a support ticket to an active Admin, Sub Admin or Super Admin.
     */
    public function assign(Request $request, int $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'admin_id' => [
                'nullable',
                'integer',
                Rule::exists('admins', 'id')->where('is_active', true),
            ],
        ]);

        $ticket = SupportTicket::query()->findOrFail($ticketId);

        if (empty($validated['admin_id'])) {
            $ticket->assignedTo()->dissociate();
        } else {
            // Only allow accounts with an authorized Admin role.
            $assignee = Admin::query()
                ->where('is_active', true)
                ->whereHas('roles', function (Builder $query) {
                    $query->where('guard_name', 'admin')
                        ->whereIn('name', [
                            'Admin',
                            'Sub Admin',
                            'Super Admin',
                        ]);
                })
                ->find($validated['admin_id']);

            if (! $assignee) {
                return response()->json([
                    'message' => 'Selected account is not eligible for ticket assignment.',
                ], 422);
            }

            $ticket->assignedTo()->associate($assignee);
        }

        $ticket->save();

        return response()->json([
            'message' => empty($validated['admin_id'])
                ? 'Ticket unassigned successfully.'
                : 'Ticket assigned successfully.',
            'data' => [
                'id' => $ticket->id,
                'assigned_to' => $ticket->assignedTo()
                    ->first(['id', 'name', 'email']),
            ],
        ]);
    }

    /**
     * Return support categories for the Admin category manager.
     */
    public function categories(): JsonResponse
    {
        $categories = SupportTicketCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_active', 'sort_order']);

        return response()->json(['data' => $categories]);
    }

    /**
     * Create a support ticket category.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:support_ticket_categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $category = SupportTicketCategory::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Support category created successfully.',
            'data' => $category,
        ], 201);
    }

    /**
     * Update an existing support ticket category.
     */
    public function updateCategory(Request $request, int $categoryId): JsonResponse
    {
        $category = SupportTicketCategory::query()->findOrFail($categoryId);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('support_ticket_categories', 'name')->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Support category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Download an attachment after verifying the Admin's ticket access permission.
     */
    public function downloadAttachment(int $attachmentId): StreamedResponse
    {
        $attachment = SupportTicketAttachment::query()
            ->with('ticket')
            ->findOrFail($attachmentId);

        $path = $attachment->file_path;

        abort_if(
            ! is_string($path) || trim($path) === '',
            404,
            'Attachment file path is missing.'
        );

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404, 'Attachment file not found.');

        return $disk->download($path, $attachment->original_name);
    }

    /**
     * Format a support ticket for the Admin ticket listing and detail screen.
     */
    private function formatTicket(SupportTicket $ticket): array
    {
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
            'requester' => [
                'name' => $ticket->requester?->name ?? 'Unknown requester',
                'email' => $ticket->requester?->email,
                'type' => class_basename($ticket->requester_type),
            ],
            'assigned_to' => $ticket->assignedTo ? [
                'id' => $ticket->assignedTo->id,
                'name' => $ticket->assignedTo->name,
                'email' => $ticket->assignedTo->email,
            ] : null,
            'last_reply_at' => $ticket->last_reply_at,
            'created_at' => $ticket->created_at,
        ];
    }

    /**
     * Format an attachment without exposing its private storage path.
     */
    private function formatAttachment(
        SupportTicketAttachment $attachment
    ): array {
        return [
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => (int) $attachment->file_size,
            'download_url' => route('api.admin.support.attachments.download', [
                'attachmentId' => $attachment->id,
            ]),
            'created_at' => $attachment->created_at,
        ];
    }

    /**
     * Store validated Admin reply or internal-note attachments using schema column names.
     */
    private function storeAdminAttachments(
        Request $request,
        SupportTicket $ticket,
        SupportTicketReply $reply,
        Admin $admin
    ): void {
        foreach ($request->file('attachments', []) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('support-tickets/' . $ticket->id, 'local');

            if ($path === false) {
                throw new \RuntimeException('Unable to store support ticket attachment.');
            }

            $attachment = new SupportTicketAttachment([
                'original_name' => mb_substr(
                    basename($file->getClientOriginalName()),
                    0,
                    255
                ),
                'file_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
            ]);

            $attachment->ticket()->associate($ticket);
            $attachment->reply()->associate($reply);
            $attachment->uploaded_by()->associate($admin);
            $attachment->save();
        }
    }



    /**
     * Return active Admin, Sub Admin and Super Admin accounts for ticket assignment.
     */
    public function assignees(): JsonResponse
    {
        $admins = Admin::query()
            ->where('is_active', true)
            ->whereHas('roles', function (Builder $query) {
                $query->where('guard_name', 'admin')
                    ->whereIn('name', [
                        'Admin',
                        'Sub Admin',
                        'Super Admin',
                    ]);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $admins,
        ]);
    }







}
