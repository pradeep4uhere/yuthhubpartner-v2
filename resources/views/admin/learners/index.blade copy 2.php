@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/admin-learners.css') }}">
@section('content')

<main class="admin-learners-page">

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <header class="admin-learners-header">

        <div class="admin-learners-header-left">

            <a
                href="{{ route('admin.dashboard') }}"
                class="admin-back-link"
            >
                <span>←</span>
                Dashboard
            </a>

        </div>

        <div class="admin-learners-header-actions">

            @can('learners.create')
                <a
                    href="{{ route('admin.learners.create') }}"
                    class="learner-btn learner-btn-primary"
                >
                    <span>+</span>
                    Add Learner
                </a>
            @endcan

            @can('learners.view')
                <form
                    method="POST"
                    action="{{ route('admin.learners.export') }}"
                >
                    @csrf

                    @foreach($filters as $key => $value)
                        @if($value !== null && $value !== '')
                            <input
                                type="hidden"
                                name="{{ $key }}"
                                value="{{ $value }}"
                            >
                        @endif
                    @endforeach

                    <button
                        type="submit"
                        class="learner-btn learner-btn-secondary"
                    >
                        <span>↓</span>
                        Export
                    </button>
                </form>
            @endcan

            <form
                method="POST"
                action="{{ route('admin.logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="learner-signout"
                >
                    Sign out
                </button>
            </form>

        </div>

    </header>


    {{-- =========================================================
        CONTENT
    ========================================================== --}}

    <section class="admin-learners-content">

        <div class="admin-learners-title">

            <div>

                <p class="learner-eyebrow">
                    LEARNER MANAGEMENT
                </p>

                <h1>
                    Learners
                </h1>

                <p>
                    Monitor learner onboarding, course progress,
                    partner activity and field agent assignments.
                </p>

            </div>

        </div>


        {{-- =====================================================
            SUCCESS MESSAGE
        ====================================================== --}}

        @if(session('status'))

            <div
                class="learner-alert learner-alert-success"
                role="status"
            >
                <span>✓</span>
                {{ session('status') }}
            </div>

        @endif


        {{-- =====================================================
            KPI CARDS
        ====================================================== --}}

        <section class="learner-kpi-grid">

            <article class="learner-kpi-card">

                <div class="learner-kpi-icon">
                    👥
                </div>

                <div class="learner-kpi-content">

                    <span>
                        Total Learners
                    </span>

                    <strong>
                        {{ number_format($totalLearners) }}
                    </strong>

                    <small>
                        Across all partners
                    </small>

                </div>

            </article>


            <article class="learner-kpi-card">

                <div class="learner-kpi-icon">
                    ↗
                </div>

                <div class="learner-kpi-content">

                    <span>
                        Last 7 Days
                    </span>

                    <strong>
                        {{ number_format($lastSevenDays) }}
                    </strong>

                    <small class="{{ $weeklyGrowth >= 0 ? 'positive' : 'negative' }}">

                        {{ $weeklyGrowth >= 0 ? '↑' : '↓' }}
                        {{ abs($weeklyGrowth) }}%

                        vs previous 7 days

                    </small>

                </div>

            </article>


            <article class="learner-kpi-card">

                <div class="learner-kpi-icon">
                    ✓
                </div>

                <div class="learner-kpi-content">

                    <span>
                        Course Completed
                    </span>

                    <strong>
                        {{ number_format($completedLearners) }}
                    </strong>

                    <small>
                        {{ $totalLearners > 0 ? round(($completedLearners / $totalLearners) * 100, 1) : 0 }}%
                        completion rate
                    </small>

                </div>

            </article>


            <article class="learner-kpi-card learner-kpi-card-warning">

                <div class="learner-kpi-icon">
                    !
                </div>

                <div class="learner-kpi-content">

                    <span>
                        Unassigned
                    </span>

                    <strong>
                        {{ number_format($unassignedLearners) }}
                    </strong>

                    <small>
                        Learners without field agent
                    </small>

                </div>

            </article>

        </section>


        {{-- =====================================================
            SECONDARY STATS
        ====================================================== --}}

        <section class="learner-secondary-stats">

            <div>
                <span>Today</span>
                <strong>{{ number_format($todayLearners) }}</strong>
            </div>

            <div>
                <span>Active</span>
                <strong>{{ number_format($activeLearners) }}</strong>
            </div>

            <div>
                <span>Pending Course</span>
                <strong>{{ number_format($pendingLearners) }}</strong>
            </div>

            <div>
                <span>Assigned</span>

                <strong>
                    {{ number_format(max(0, $totalLearners - $unassignedLearners)) }}
                </strong>
            </div>

        </section>


        {{-- =====================================================
            ANALYTICS
        ====================================================== --}}

        <section class="learner-analytics-grid">

            <div class="learner-analytics-card">

                <div class="learner-card-heading">

                    <div>
                        <span>REGISTRATION ACTIVITY</span>
                        <h2>Last 7 Days</h2>
                    </div>

                    <div class="learner-card-total">
                        {{ number_format($lastSevenDays) }}
                    </div>

                </div>

                <div class="learner-chart">

                    @php
                        $maxTrend = max(1, $registrationTrend->max('total'));
                    @endphp

                    @foreach($registrationTrend as $item)

                        <div class="learner-chart-column">

                            <div class="learner-chart-value">
                                {{ $item['total'] }}
                            </div>

                            <div class="learner-chart-track">

                                <div
                                    class="learner-chart-bar"
                                    style="height: {{ max(5, ($item['total'] / $maxTrend) * 100) }}%;"
                                ></div>

                            </div>

                            <span>
                                {{ $item['label'] }}
                            </span>

                        </div>

                    @endforeach

                </div>

            </div>


            <div class="learner-analytics-card learner-progress-card">

                <div class="learner-card-heading">

                    <div>
                        <span>COURSE PROGRESS</span>
                        <h2>Completion Overview</h2>
                    </div>

                </div>

                @php
                    $completionPercentage =
                        $totalLearners > 0
                        ? round(($completedLearners / $totalLearners) * 100, 1)
                        : 0;
                @endphp

                <div class="learner-progress-circle">

                    <div>
                        <strong>
                            {{ $completionPercentage }}%
                        </strong>

                        <span>
                            Completed
                        </span>
                    </div>

                </div>

                <div class="learner-progress-legend">

                    <span>
                        <i class="completed"></i>
                        Completed
                        <b>{{ number_format($completedLearners) }}</b>
                    </span>

                    <span>
                        <i class="pending"></i>
                        Pending
                        <b>{{ number_format($pendingLearners) }}</b>
                    </span>

                </div>

            </div>

        </section>



        {{-- =====================================================
            FILTER + TABLE PANEL
        ====================================================== --}}

        <section class="learner-table-panel mt-5">

            <div class="learner-table-heading">

                <div>

                    <span>
                        LEARNER DIRECTORY
                    </span>

                    <h2>
                        All Learners
                    </h2>

                </div>

                <span class="learner-result-count">

                    {{ number_format($learners->total()) }}
                    matching records

                </span>

            </div>


            {{-- FILTERS --}}

            <form
                method="GET"
                action="{{ route('admin.learners.index') }}"
                class="learner-filters"
            >

                <div class="learner-search">

                    <span>⌕</span>

                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Search name, mobile or program..."
                    >

                </div>


                <select name="partner_id">

                    <option value="">
                        All Partners
                    </option>

                    @foreach($partners as $partner)

                        <option
                            value="{{ $partner->id }}"
                            @selected(
                                (string) $filters['partner_id']
                                ===
                                (string) $partner->id
                            )
                        >
                            {{ $partner->name }}
                        </option>

                    @endforeach

                </select>


                <select name="partner_center_id">

                    <option value="">
                        All Centers
                    </option>

                    @foreach($partnerCenters as $center)

                        <option
                            value="{{ $center->id }}"
                            @selected(
                                (string) $filters['partner_center_id']
                                ===
                                (string) $center->id
                            )
                        >
                            {{ $center->name }}
                        </option>

                    @endforeach

                </select>


                <select name="field_agent_id">

                    <option value="">
                        All Field Agents
                    </option>

                    @foreach($fieldAgents as $agent)

                        <option
                            value="{{ $agent->id }}"
                            @selected(
                                (string) $filters['field_agent_id']
                                ===
                                (string) $agent->id
                            )
                        >
                            {{ $agent->name }}
                        </option>

                    @endforeach

                </select>


                <select name="course_completed">

                    <option value="">
                        Course Status
                    </option>

                    <option
                        value="1"
                        @selected($filters['course_completed'] === '1')
                    >
                        Completed
                    </option>

                    <option
                        value="0"
                        @selected($filters['course_completed'] === '0')
                    >
                        Pending
                    </option>

                </select>


                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="active"
                        @selected($filters['status'] === 'active')
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        @selected($filters['status'] === 'inactive')
                    >
                        Inactive
                    </option>

                </select>


                <input
                    type="text"
                    name="program_code"
                    value="{{ $filters['program_code'] }}"
                    placeholder="Program code"
                >


                <input
                    type="date"
                    name="date_from"
                    value="{{ $filters['date_from'] }}"
                >

                <input
                    type="date"
                    name="date_to"
                    value="{{ $filters['date_to'] }}"
                >


                <div class="learner-filter-actions">

                    <button
                        type="submit"
                        class="learner-btn learner-btn-primary"
                    >
                        Apply Filters
                    </button>

                    <a
                        href="{{ route('admin.learners.index') }}"
                        class="learner-clear-btn"
                    >
                        Clear
                    </a>



                </div>


            </form>


            {{-- TABLE --}}

            <div class="learner-table-wrap">

                <table class="learner-table">

                    <thead>

                        <tr>

                            <th class="checkbox-column">
                                <input
                                    type="checkbox"
                                    id="select-all-learners"
                                >
                            </th>

                            <th>Learner</th>
                            <th>Mobile</th>
                            <th>Program</th>
                            <th>Partner</th>
                            <th>Field Agent</th>
                            <th>Location</th>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($learners as $learner)

                            <tr>

                                <td>
                                    <input
                                        type="checkbox"
                                        name="learner_ids[]"
                                        value="{{ $learner->id }}"
                                        class="learner-checkbox"
                                    >
                                </td>


                                <td>

                                    <div class="learner-person">

                                        <div class="learner-avatar">
                                            {{ strtoupper(substr($learner->first_name, 0, 1)) }}
                                        </div>

                                        <div>

                                            <strong>
                                                {{ trim($learner->first_name . ' ' . $learner->last_name) }}
                                            </strong>

                                            <small>
                                                {{ $learner->gender ?: 'Gender not specified' }}
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <span class="learner-mobile">
                                        {{ $learner->normalized_mobile ?: $learner->phone_number ?: '—' }}
                                    </span>

                                </td>


                                <td>

                                    <span class="learner-program">
                                        {{ $learner->program_code ?: '—' }}
                                    </span>

                                </td>


                                <td>

                                    <div class="learner-partner">

                                        <strong>
                                            {{ $learner->partner?->name ?: '—' }}
                                        </strong>

                                        @if($learner->partnerCenter)
                                            <small>
                                                {{ $learner->partnerCenter->name }}
                                            </small>
                                        @endif

                                    </div>

                                </td>


                                <td>

                                    @if($learner->fieldAgent)

                                        <div class="learner-agent">

                                            <strong>
                                                {{ $learner->fieldAgent->name }}
                                            </strong>

                                            <small>
                                                {{ $learner->fieldAgent->sakhi_id }}
                                            </small>

                                        </div>

                                    @else

                                        <span class="learner-unassigned">
                                            Unassigned
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <div class="learner-location">

                                        <strong>
                                            {{ $learner->city ?: '—' }}
                                        </strong>

                                        @if($learner->state)
                                            <small>
                                                {{ $learner->state }}
                                            </small>
                                        @endif

                                    </div>

                                </td>


                                <td>

                                    @if($learner->course_completed)

                                        <span class="learner-status learner-status-completed">
                                            ● Completed
                                        </span>

                                    @else

                                        <span class="learner-status learner-status-pending">
                                            ● Pending
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <span
                                        class="learner-status
                                        {{ $learner->status === 'active'
                                            ? 'learner-status-active'
                                            : 'learner-status-inactive' }}"
                                    >
                                        ● {{ ucfirst($learner->status) }}
                                    </span>

                                </td>


                                <td>

                                    <div class="learner-actions">

                                        @can('learners.update')

                                            <a
                                                href="{{ route('admin.learners.edit', $learner) }}"
                                                title="Edit"
                                            >
                                                Edit
                                            </a>

                                        @endcan

                                        @can('learners.delete')

                                            <form
                                                method="POST"
                                                action="{{ route('admin.learners.destroy', $learner) }}"
                                                onsubmit="return confirm('Delete this learner?')"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit">
                                                    Delete
                                                </button>

                                            </form>

                                        @endcan

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="learner-empty"
                                >
                                    <div>
                                        <strong>
                                            No learners found
                                        </strong>

                                        <span>
                                            Try changing your search or filters.
                                        </span>
                                    </div>
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- FOOTER --}}

            <footer class="learner-table-footer">

                <span>

                    Showing
                    <strong>{{ $learners->firstItem() ?? 0 }}</strong>
                    –
                    <strong>{{ $learners->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ number_format($learners->total()) }}</strong>

                </span>

                <div>
                    {{ $learners->onEachSide(1)->links() }}
                </div>

            </footer>

        </section>

    </section>

</main>

@endsection
