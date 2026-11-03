@extends('layouts.app')

@section('content')

{{-- Event Transactions Page Styles --}}
<style>
    .aet-page-shell {
        width: 100%;
        min-height: 100vh;
        padding: 0 30px 35px;
        box-sizing: border-box;
        background: #f4f7fc;
    }

    /* Top navigation header */
    .aet-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 68px;
        margin: 0 -30px;
        padding: 0 40px;
        background: #fff;
        border-bottom: 1px solid #e8edf5;
    }

    .aet-back-dashboard {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #315b9b;
        font-size: 13px;
        font-weight: 650;
        text-decoration: none;
    }

    .aet-back-dashboard:hover {
        color: #1749a8;
    }

    .aet-signout-btn {
        min-height: 36px;
        padding: 0 16px;
        border: 1px solid #dce5ef;
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-size: 12px;
        font-weight: 650;
        cursor: pointer;
    }

    .aet-signout-btn:hover {
        color: #d04444;
        background: #fff7f7;
        border-color: #f1cccc;
    }

    /* Page title section */
    .aet-page-intro {
        padding: 34px 0 25px;
    }

    .aet-page-eyebrow {
        display: block;
        margin-bottom: 9px;
        color: #3977e8;
        font-size: 10px;
        font-weight: 750;
        letter-spacing: 1.3px;
    }

    .aet-page-intro h1 {
        margin: 0;
        color: #142746;
        font-size: 30px;
        font-weight: 750;
        line-height: 1.25;
        letter-spacing: -1px;
    }

    .aet-page-intro p {
        margin: 8px 0 0;
        color: #7b8ba4;
        font-size: 13px;
    }

    /* React root */
    #admin-event-transactions-root {
        width: 100%;
        min-width: 0;
    }

    @media (max-width: 768px) {
        .aet-page-shell {
            padding: 0 14px 24px;
        }

        .aet-page-header {
            margin: 0 -14px;
            padding: 0 16px;
            min-height: 60px;
        }

        .aet-page-intro {
            padding: 25px 0 20px;
        }

        .aet-page-intro h1 {
            font-size: 24px;
        }

        .aet-page-intro p {
            font-size: 12px;
            line-height: 1.6;
        }
    }
</style>
<div class="aet-page-shell">

    {{-- Top Navigation --}}
    <header class="aet-page-header">

        <a href="{{ route('admin.dashboard') }}" class="aet-back-dashboard">
            <span>←</span>
            <span>Back to Dashboard</span>
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit" class="aet-signout-btn">
                Sign out
            </button>
        </form>

    </header>

    {{-- Page Heading --}}
    <section class="aet-page-intro">

        <span class="aet-page-eyebrow">
            EVENT MANAGEMENT
        </span>

        <h1>Event Transactions</h1>

        <p>
            Monitor event submissions, beneficiary activity and transaction workflow.
        </p>

    </section>

    {{-- React Event Transactions Directory --}}
    <div
        id="admin-event-transactions-root"
        data-react-app="admin-event-transactions"
        data-admin-name="{{ auth('admin')->user()->name }}"
        data-listing-url="{{ route('admin.event-transactions.data') }}"
        data-filter-options-url="{{ route('admin.event-transactions.filter-options') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-dashboard-url="{{ route('admin.dashboard') }}"
    ></div>

</div>

@endsection
