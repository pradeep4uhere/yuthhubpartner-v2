@extends('layouts.app')

@section('content')

<style>
/* =========================================================
   EVENT TRANSACTION SHOW PAGE
   Isolated Professional UI
   ========================================================= */

.event-transaction-show-page {
    min-height: 100vh !important;
    width: 100% !important;
    background:
        radial-gradient(circle at top right, rgba(16, 185, 129, .06), transparent 30%),
        #f5f7fa !important;
    padding: 32px 24px 60px !important;
    box-sizing: border-box !important;
}

.event-transaction-show-page *,
.event-transaction-show-page *::before,
.event-transaction-show-page *::after {
    box-sizing: border-box !important;
}

.event-transaction-show-content {
    width: 100% !important;
    max-width: 1380px !important;
    margin: 0 auto !important;
}

/* =========================================================
   TOP HEADER
   ========================================================= */

.event-transaction-show-content > .admin-users-header {
    width: 100% !important;
    max-width: none !important;
    margin: 0 0 28px !important;
    padding: 0 !important;
}

.event-transaction-show-content > .admin-users-header a {
    color: #475569 !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
}

.event-transaction-show-content > .admin-users-header a:hover {
    color: #0f172a !important;
}

.event-transaction-show-content > .admin-users-header button {
    color: #64748b !important;
    font-size: 13px !important;
    font-weight: 600 !important;
}

/* =========================================================
   PAGE HEADING
   ========================================================= */

.event-transaction-show-content > .admin-partners-heading {
    display: flex !important;
    align-items: flex-end !important;
    justify-content: space-between !important;
    gap: 30px !important;
    margin: 0 0 28px !important;
    padding: 0 !important;
}

.event-transaction-show-content > .admin-partners-heading h1 {
    margin: 3px 0 0 !important;
    color: #0f172a !important;
    font-size: 34px !important;
    line-height: 1.15 !important;
    font-weight: 800 !important;
    letter-spacing: -1px !important;
}

.event-transaction-show-content .admin-users-intro {
    margin: 9px 0 0 !important;
    color: #64748b !important;
    font-size: 14px !important;
    line-height: 1.6 !important;
}

.event-transaction-show-content .admin-auth-eyebrow {
    margin: 0 !important;
    color: #10b981 !important;
    font-size: 10px !important;
    line-height: 1.4 !important;
    font-weight: 800 !important;
    letter-spacing: .12em !important;
}

/* =========================================================
   PANELS
   ========================================================= */

.event-transaction-show-content > .admin-partners-panel {
    width: 100% !important;
    margin: 0 0 20px !important;
    padding: 0 !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow:
        0 1px 2px rgba(15, 23, 42, .03),
        0 10px 30px rgba(15, 23, 42, .045) !important;
    overflow: hidden !important;
}

.event-transaction-show-content > .admin-partners-panel:last-child {
    margin-bottom: 0 !important;
}

/* =========================================================
   SECTION HEADING
   ========================================================= */

.event-transaction-show-content
.admin-partners-panel
> .admin-partners-heading {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    min-height: 82px !important;
    margin: 0 !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid #edf1f5 !important;
    background: #ffffff !important;
}

.event-transaction-show-content
.admin-partners-panel
> .admin-partners-heading h2 {
    margin: 3px 0 0 !important;
    color: #172033 !important;
    font-size: 17px !important;
    line-height: 1.3 !important;
    font-weight: 750 !important;
    letter-spacing: -.2px !important;
}

/* =========================================================
   STATUS CARD
   ========================================================= */

.event-transaction-show-content .admin-event-status-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    min-height: 105px !important;
    padding: 24px 26px !important;
    background:
        linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
}

.event-transaction-show-content
.admin-event-status-header h2 {
    margin: 4px 0 0 !important;
    color: #0f172a !important;
    font-size: 19px !important;
    font-weight: 750 !important;
}

/* =========================================================
   STATUS BADGE
   ========================================================= */

.event-transaction-show-content .admin-partner-status {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 34px !important;
    padding: 7px 14px !important;
    border-radius: 999px !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: .02em !important;
    white-space: nowrap !important;
}

.event-transaction-show-content
.admin-partner-status--active {
    background: #dcfce7 !important;
    color: #15803d !important;
}

.event-transaction-show-content
.admin-partner-status--inactive {
    background: #fee2e2 !important;
    color: #b91c1c !important;
}

.event-transaction-show-content
.admin-partner-status--pending {
    background: #fef3c7 !important;
    color: #b45309 !important;
}

/* =========================================================
   INFORMATION GRID
   ========================================================= */

.event-transaction-show-content .admin-event-details-grid {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 0 !important;
    width: 100% !important;
    background: #ffffff !important;
}

.event-transaction-show-content .admin-event-details-grid > div {
    min-width: 0 !important;
    min-height: 105px !important;
    padding: 20px 22px !important;
    background: #ffffff !important;
    border: 0 !important;
    border-right: 1px solid #edf1f5 !important;
    border-bottom: 1px solid #edf1f5 !important;
}

.event-transaction-show-content
.admin-event-details-grid > div:nth-child(4n) {
    border-right: 0 !important;
}

.event-transaction-show-content
.admin-event-details-grid
> div:nth-last-child(-n+4) {
    border-bottom: 0 !important;
}

.event-transaction-show-content
.admin-event-details-grid
small {
    display: block !important;
    margin: 0 0 8px !important;
    color: #94a3b8 !important;
    font-size: 10px !important;
    line-height: 1.3 !important;
    font-weight: 800 !important;
    letter-spacing: .08em !important;
    text-transform: uppercase !important;
}

.event-transaction-show-content
.admin-event-details-grid
strong {
    display: block !important;
    max-width: 100% !important;
    color: #1e293b !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    font-weight: 700 !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
}

.event-transaction-show-content
.admin-event-details-grid
> div:nth-child(4)
strong {
    color: #059669 !important;
    font-size: 18px !important;
    font-weight: 800 !important;
}

/* =========================================================
   DOCUMENTS
   ========================================================= */

.event-transaction-show-content .admin-event-documents {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 16px !important;
    padding: 24px !important;
    background: #ffffff !important;
}

.event-transaction-show-content .admin-event-document {
    min-width: 0 !important;
    padding: 18px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    transition: all .2s ease !important;
}

.event-transaction-show-content .admin-event-document:hover {
    background: #ffffff !important;
    border-color: #cbd5e1 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 5px 15px rgba(15, 23, 42, .05) !important;
}

.event-transaction-show-content
.admin-event-document
small {
    display: block !important;
    margin-bottom: 10px !important;
    color: #64748b !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    letter-spacing: .07em !important;
    text-transform: uppercase !important;
}

.event-transaction-show-content
.admin-event-document
a {
    color: #2563eb !important;
    font-size: 13px !important;
    font-weight: 750 !important;
    text-decoration: none !important;
}

.event-transaction-show-content
.admin-event-document
a:hover {
    text-decoration: underline !important;
}

.event-transaction-show-content
.admin-event-document
span {
    color: #94a3b8 !important;
    font-size: 13px !important;
}

/* =========================================================
   COMMENT
   ========================================================= */

.event-transaction-show-content .admin-event-comment {
    margin: 0 !important;
    padding: 22px 24px !important;
    background: #fffbeb !important;
    border: 0 !important;
    border-left: 4px solid #f59e0b !important;
    border-radius: 0 !important;
    color: #57534e !important;
    font-size: 14px !important;
    line-height: 1.7 !important;
}

/* =========================================================
   ADMIN ACTION AREA
   ========================================================= */

.event-transaction-show-content .admin-event-actions {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 24px !important;
    background: #ffffff !important;
}

.event-transaction-show-content
.admin-event-actions
form {
    margin: 0 !important;
}

.event-transaction-show-content
.admin-event-actions
a,
.event-transaction-show-content
.admin-event-actions
button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 42px !important;
    padding: 10px 17px !important;
    border: 1px solid #d7dee7 !important;
    border-radius: 9px !important;
    background: #ffffff !important;
    color: #334155 !important;
    font-family: inherit !important;
    font-size: 12px !important;
    font-weight: 750 !important;
    line-height: 1 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: all .2s ease !important;
}

.event-transaction-show-content
.admin-event-actions
a:hover,
.event-transaction-show-content
.admin-event-actions
button:hover {
    border-color: #94a3b8 !important;
    background: #f8fafc !important;
    color: #0f172a !important;
}

.event-transaction-show-content
.admin-event-actions
.btn-primary {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
}

.event-transaction-show-content
.admin-event-actions
.btn-success {
    background: #059669 !important;
    border-color: #059669 !important;
    color: #ffffff !important;
}

.event-transaction-show-content
.admin-event-actions
.btn-danger {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}

/* =========================================================
   REJECT FORM
   ========================================================= */

.event-transaction-show-content
.admin-event-actions
details {
    width: 100% !important;
    max-width: 620px !important;
}

.event-transaction-show-content
.admin-event-actions
summary {
    display: inline-flex !important;
    align-items: center !important;
    min-height: 42px !important;
    padding: 10px 17px !important;
    border: 1px solid #fecaca !important;
    border-radius: 9px !important;
    background: #fff !important;
    color: #dc2626 !important;
    font-size: 12px !important;
    font-weight: 750 !important;
    cursor: pointer !important;
}

.event-transaction-show-content
.admin-event-actions
textarea {
    display: block !important;
    width: 100% !important;
    min-height: 120px !important;
    padding: 13px 15px !important;
    border: 1px solid #d7dee7 !important;
    border-radius: 10px !important;
    background: #ffffff !important;
    color: #1e293b !important;
    font-family: inherit !important;
    font-size: 13px !important;
    resize: vertical !important;
    outline: none !important;
}

.event-transaction-show-content
.admin-event-actions
textarea:focus {
    border-color: #94a3b8 !important;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, .05) !important;
}

/* =========================================================
   TIMELINE
   ========================================================= */

.event-transaction-show-content .admin-event-timeline {
    position: relative !important;
    padding: 28px 28px 28px 34px !important;
    background: #ffffff !important;
}

.event-transaction-show-content .admin-event-timeline::before {
    content: "" !important;
    position: absolute !important;
    left: 47px !important;
    top: 35px !important;
    bottom: 35px !important;
    width: 1px !important;
    background: #e2e8f0 !important;
}

.event-transaction-show-content
.admin-event-timeline-item {
    position: relative !important;
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    gap: 25px !important;
    padding: 0 0 28px 48px !important;
}

.event-transaction-show-content
.admin-event-timeline-item:last-child {
    padding-bottom: 0 !important;
}

.event-transaction-show-content
.admin-event-timeline-item::before {
    content: "" !important;
    position: absolute !important;
    left: 8px !important;
    top: 2px !important;
    width: 15px !important;
    height: 15px !important;
    border: 4px solid #ffffff !important;
    border-radius: 50% !important;
    background: #10b981 !important;
    box-shadow: 0 0 0 1px #cbd5e1 !important;
    z-index: 2 !important;
}

.event-transaction-show-content
.admin-event-timeline-item
strong {
    display: block !important;
    margin-bottom: 5px !important;
    color: #1e293b !important;
    font-size: 13px !important;
    font-weight: 800 !important;
}

.event-transaction-show-content
.admin-event-timeline-item
p {
    margin: 5px 0 0 !important;
    color: #64748b !important;
    font-size: 12px !important;
    line-height: 1.6 !important;
}

.event-transaction-show-content
.admin-event-timeline-item
small {
    color: #94a3b8 !important;
    font-size: 10px !important;
}

.admin-event-actions {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 1px;
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 1100px) {

    .event-transaction-show-content
    .admin-event-details-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-child(4n) {
        border-right: 1px solid #edf1f5 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-child(2n) {
        border-right: 0 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-last-child(-n+4) {
        border-bottom: 1px solid #edf1f5 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:nth-last-child(-n+2) {
        border-bottom: 0 !important;
    }
}

@media (max-width: 760px) {

    .event-transaction-show-page {
        padding: 20px 14px 40px !important;
    }

    .event-transaction-show-content
    > .admin-partners-heading {
        align-items: flex-start !important;
        flex-direction: column !important;
    }

    .event-transaction-show-content
    > .admin-partners-heading
    h1 {
        font-size: 28px !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid {
        grid-template-columns: 1fr !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div {
        border-right: 0 !important;
        border-bottom: 1px solid #edf1f5 !important;
    }

    .event-transaction-show-content
    .admin-event-details-grid
    > div:last-child {
        border-bottom: 0 !important;
    }

    .event-transaction-show-content
    .admin-event-documents {
        grid-template-columns: 1fr !important;
        padding: 18px !important;
    }

    .event-transaction-show-content
    .admin-event-status-header {
        align-items: flex-start !important;
        flex-direction: column !important;
    }

    .event-transaction-show-content
    .admin-event-actions {
        align-items: stretch !important;
        flex-direction: column !important;
    }

    .event-transaction-show-content
    .admin-event-actions
    a,
    .event-transaction-show-content
    .admin-event-actions
    button {
        width: 100% !important;
    }

    .event-transaction-show-content
    .admin-event-timeline-item {
        grid-template-columns: 1fr !important;
        gap: 5px !important;
    }
}





</style>
@php
    $workflowStatus = $eventTransaction->workflow_status;

    $statusClass = match ($workflowStatus) {
        'approved' => 'active',
        'rejected' => 'inactive',
        default => 'pending',
    };

    $statusLabel = match ($workflowStatus) {
        'under_review' => 'Under Review',
        'resubmitted' => 'Resubmitted',
        default => ucfirst($workflowStatus ?: 'Unknown'),
    };
@endphp

<main class="event-transaction-show-page">

    {{-- Header --}}
    <header class="admin-users-header">

        <a href="{{ route('admin.event-transactions.index') }}">
            &larr; Event Transactions
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit">
                Sign out
            </button>
        </form>

    </header>


    <section class="event-transaction-show-content">

        {{-- Page Heading --}}
        <div class="admin-partners-heading">

            <div>

                <p class="admin-auth-eyebrow">
                    EVENT TRANSACTION #{{ $eventTransaction->id }}
                </p>

                <h1>
                    Event Transaction Details
                </h1>

                <p class="admin-users-intro">
                    Review transaction information, workflow status and activity history.
                </p>

            </div>


            <div class="admin-partner-actions">

                @can('update', $eventTransaction)

                    <a href="{{ route('admin.event-transactions.edit', $eventTransaction) }}">
                        Edit
                    </a>

                @endcan

            </div>

        </div>


        {{-- Success Message --}}
        @if (session('status'))

            <div class="admin-users-success" role="status">
                {{ session('status') }}
            </div>

        @endif


        {{-- Error Message --}}
        @if (session('error'))

            <div class="admin-users-error" role="alert">
                {{ session('error') }}
            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- CURRENT STATUS --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-event-status-header">

                <div>

                    <p class="admin-auth-eyebrow">
                        CURRENT WORKFLOW
                    </p>

                    <h2>
                        Transaction Status
                    </h2>

                </div>

                <span class="admin-partner-status admin-partner-status--{{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- TRANSACTION INFORMATION --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        TRANSACTION
                    </p>

                    <h2>
                        Transaction Information
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Transaction ID</small>

                    <strong>
                        #{{ $eventTransaction->id }}
                    </strong>
                </div>


                <div>
                    <small>Workflow Status</small>

                    <strong>
                        {{ $statusLabel }}
                    </strong>
                </div>


                <div>
                    <small>Review Status</small>

                    <strong>
                        {{ $eventTransaction->review_status
                            ? ucfirst(str_replace('_', ' ', $eventTransaction->review_status))
                            : '—' }}
                    </strong>
                </div>


                <div>
                    <small>Event Value</small>

                    <strong>
                        @if ($eventTransaction->event_value !== null)
                            ₹{{ number_format((float) $eventTransaction->event_value, 2) }}
                        @else
                            —
                        @endif
                    </strong>
                </div>


                <div>
                    <small>Created At</small>

                    <strong>
                        {{ $eventTransaction->created_at?->format('d M Y, h:i A') ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Last Updated</small>

                    <strong>
                        {{ $eventTransaction->updated_at?->format('d M Y, h:i A') ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Event Created Date</small>

                    <strong>
                        {{ $eventTransaction->event_create_date?->format('d M Y') ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Submitted Date</small>

                    <strong>
                        {{ $eventTransaction->event_date_submitted?->format('d M Y') ?: '—' }}
                    </strong>
                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- BENEFICIARY --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        BENEFICIARY
                    </p>

                    <h2>
                        Beneficiary Information
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Name</small>

                    <strong>
                        {{ $eventTransaction->beneficiary_name ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Mobile Number</small>

                    <strong>
                        {{ $eventTransaction->beneficiary_phone_number ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Learner ID</small>

                    <strong>
                        {{ $eventTransaction->learner_id ?: '—' }}
                    </strong>
                </div>


                @if ($eventTransaction->learner)

                    <div>
                        <small>Learner</small>

                        <strong>
                            {{ trim(
                                ($eventTransaction->learner->first_name ?? '') . ' ' .
                                ($eventTransaction->learner->last_name ?? '')
                            ) ?: '—' }}
                        </strong>
                    </div>

                @endif

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- EVENT INFORMATION --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        EVENT
                    </p>

                    <h2>
                        Event Information
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Event Master ID</small>

                    <strong>
                        {{ $eventTransaction->event_master_id ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Event Master Type ID</small>

                    <strong>
                        {{ $eventTransaction->event_master_type_id ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Event Type</small>

                    <strong>
                        {{ $eventTransaction->event_type_name ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Event Category</small>

                    <strong>
                        {{ $eventTransaction->event_category_name ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Field Type</small>

                    <strong>
                        {{ $eventTransaction->field_type_id ?: '—' }}
                    </strong>
                </div>


                <div>
                    <small>Industry Type</small>

                    <strong>
                        {{ $eventTransaction->industry_type_id ?: '—' }}
                    </strong>
                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- ASSIGNMENT --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        ASSIGNMENT
                    </p>

                    <h2>
                        Partner & Field Agent
                    </h2>

                </div>

            </div>


            <div class="admin-event-details-grid">

                <div>
                    <small>Partner</small>

                    <strong>
                        {{ $eventTransaction->partner?->name ?: '—' }}
                    </strong>

                    @if ($eventTransaction->partner_id)
                        <small>
                            ID: {{ $eventTransaction->partner_id }}
                        </small>
                    @endif
                </div>


                <div>
                    <small>Partner Center</small>

                    <strong>
                        {{ $eventTransaction->partnerCenter?->name ?: '—' }}
                    </strong>

                    @if ($eventTransaction->partner_center_id)
                        <small>
                            ID: {{ $eventTransaction->partner_center_id }}
                        </small>
                    @endif
                </div>


                <div>
                    <small>Partner Placement User</small>

                    <strong>
                        {{ $eventTransaction->partnerPlacementUser?->name ?: '—' }}
                    </strong>

                    @if ($eventTransaction->partner_placement_user_id)
                        <small>
                            ID: {{ $eventTransaction->partner_placement_user_id }}
                        </small>
                    @endif
                </div>


                <div>
                    <small>Field Agent</small>

                    @if ($eventTransaction->fieldAgent)

                        <strong>
                            {{ $eventTransaction->fieldAgent->name }}
                        </strong>

                        <small>
                            Sakhi ID:
                            {{ $eventTransaction->fieldAgent->sakhi_id }}
                        </small>

                    @else

                        <strong>
                            —
                        </strong>

                    @endif

                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- DOCUMENTS --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        DOCUMENTS
                    </p>

                    <h2>
                        Uploaded Documents
                    </h2>

                </div>

            </div>


            <div class="admin-event-documents">

                @foreach ([
                    'uploaded_doc_1' => 'Document 1',
                    'uploaded_doc_2' => 'Document 2',
                    'uploaded_doc_3' => 'Document 3',
                ] as $field => $label)

                    <div class="admin-event-document">

                        <small>
                            {{ $label }}
                        </small>

                        @if ($eventTransaction->{$field})

                            <a href="{{ $eventTransaction->{$field} }}"
                                target="_blank"
                                rel="noopener noreferrer">
                                View Document
                            </a>

                        @else

                            <span>
                                Not uploaded
                            </span>

                        @endif

                    </div>

                @endforeach


                @if ($eventTransaction->uploaded_doc_links)

                    <div class="admin-event-document">

                        <small>
                            Additional Document Links
                        </small>

                        <div>
                            {{ $eventTransaction->uploaded_doc_links }}
                        </div>

                    </div>

                @endif

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- LAST COMMENT --}}
        {{-- ========================================================= --}}

        @if ($eventTransaction->last_comment)

            <section class="admin-partners-panel">

                <div class="admin-partners-heading">

                    <div>

                        <p class="admin-auth-eyebrow">
                            COMMENT
                        </p>

                        <h2>
                            Latest Comment
                        </h2>

                    </div>

                </div>


                <div style="padding:5px;">
                    {{ $eventTransaction->last_comment }}
                </div>

            </section>

        @endif


        {{-- ========================================================= --}}
        {{-- ADMIN WORKFLOW ACTIONS --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        ADMIN ACTIONS
                    </p>

                    <h2>
                        Review Transaction
                    </h2>

                </div>

            </div>


            <div class="admin-event-actions">


                {{-- Submitted / Resubmitted → Under Review --}}
                @if (in_array($workflowStatus, ['submitted', 'resubmitted']))

                    @can('underReview', $eventTransaction)

                        <form method="POST"
                            action="{{ route('admin.event-transactions.under-review', $eventTransaction) }}">

                            @csrf

                            <button type="submit"
                                onclick="return confirm('Move this transaction to Under Review?')">

                                Move to Under Review

                            </button>

                        </form>

                    @endcan

                @endif


                {{-- Under Review → Approve / Reject --}}
                @if ($workflowStatus === 'under_review')

                    @can('approve', $eventTransaction)

                        <form method="POST"
                            action="{{ route('admin.event-transactions.approve', $eventTransaction) }}"
                            onsubmit="return confirm('Are you sure you want to approve this transaction?')">

                            @csrf

                            <button type="submit">
                                Approve
                            </button>

                        </form>

                    @endcan


                    @can('reject', $eventTransaction)

                        <details>

                            <summary>
                                Reject Transaction
                            </summary>


                            <form method="POST"
                                action="{{ route('admin.event-transactions.reject', $eventTransaction) }}"
                                style="margin-top:15px;">

                                @csrf


                                <label>
                                    <span class="visually-hidden">
                                        Rejection reason
                                    </span>

                                    <textarea
                                        name="comment"
                                        rows="4"
                                        required
                                        placeholder="Enter rejection reason..."></textarea>
                                </label>


                                <button type="submit">
                                    Confirm Rejection
                                </button>

                            </form>

                        </details>

                    @endcan

                @endif


                {{-- Edit --}}
                @can('update', $eventTransaction)

                    <a href="{{ route('admin.event-transactions.edit', $eventTransaction) }}">
                        Edit Transaction
                    </a>

                @endcan

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- ACTIVITY TIMELINE --}}
        {{-- ========================================================= --}}

        <section class="admin-partners-panel">

            <div class="admin-partners-heading">

                <div>

                    <p class="admin-auth-eyebrow">
                        AUDIT TRAIL
                    </p>

                    <h2>
                        Activity Timeline
                    </h2>

                </div>

            </div>


            @if ($eventTransaction->logs->count())

                <div class="admin-event-timeline">

                    @foreach ($eventTransaction->logs->sortByDesc('created_at') as $log)

                        <article class="admin-event-timeline-item">

                            <div>

                                <strong>
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                </strong>


                                @if ($log->old_status || $log->new_status)

                                    <p>

                                        @if ($log->old_status)
                                            {{ ucfirst(str_replace('_', ' ', $log->old_status)) }}
                                        @else
                                            —
                                        @endif

                                        →

                                        @if ($log->new_status)
                                            {{ ucfirst(str_replace('_', ' ', $log->new_status)) }}
                                        @else
                                            —
                                        @endif

                                    </p>

                                @endif


                                @if ($log->comment)

                                    <p>
                                        {{ $log->comment }}
                                    </p>

                                @endif

                            </div>


                            <div>

                                <small>
                                    {{ $log->created_at?->format('d M Y, h:i A') }}
                                </small>

                                @if ($log->user_id)

                                    <br>

                                    <small>
                                        User ID: {{ $log->user_id }}
                                    </small>

                                @endif

                            </div>

                        </article>

                    @endforeach

                </div>

            @else

                <p>
                    No activity recorded yet.
                </p>

            @endif

        </section>


    </section>

</main>


@endsection
