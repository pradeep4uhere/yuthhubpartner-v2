@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/admin-field-agents.css') }}">

@section('content')

<main class="admin-field-agents-page">

{{-- =========================================================
     HEADER
========================================================== --}}
<header class="admin-field-agents-header">

    <div class="admin-field-agents-header-left">
        <a href="{{ route('admin.dashboard') }}" class="admin-back-link">
            <span>←</span>
            Dashboard
        </a>
    </div>

    <div class="admin-field-agents-header-actions">

        @can('field_agents.create')
            <a href="{{ route('admin.field-agents.create') }}"
               class="field-agent-btn field-agent-btn-primary">
                <span>+</span>
                Add Field Agent
            </a>
        @endcan


        @can('field_agents.export')
            <form method="POST"
                  action="{{ route('admin.field-agents.export') }}">
                @csrf

                {{-- Preserve active filters --}}
                @foreach($filters ?? [] as $key => $value)
                    @if($value !== null && $value !== '')
                        <input type="hidden"
                               name="{{ $key }}"
                               value="{{ $value }}">
                    @endif
                @endforeach

                <button type="submit"
                        class="field-agent-btn field-agent-btn-secondary">
                    <span>↓</span>
                    Export
                </button>
            </form>
        @endcan


        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit"
                    class="field-agent-signout">
                Sign out
            </button>
        </form>

    </div>

</header>


{{-- =========================================================
     CONTENT
========================================================== --}}
<section class="admin-field-agents-content">


    {{-- =====================================================
         PAGE TITLE
    ====================================================== --}}
    <div class="admin-field-agents-title">

        <div>

            <p class="field-agent-eyebrow">
                FIELD OPERATIONS
            </p>

            <h1>
                Field Agents
            </h1>

            <p>
                Manage field teams, partner assignments,
                placement operations and Sakhi registrations.
            </p>

        </div>

    </div>


    {{-- =====================================================
         SUCCESS MESSAGE
    ====================================================== --}}
    @if(session('status'))

        <div class="field-agent-alert field-agent-alert-success"
             role="status">

            <span>✓</span>

            {{ session('status') }}

        </div>

    @endif


    {{-- =====================================================
         ERROR MESSAGE
    ====================================================== --}}
    @if(session('error'))

        <div class="field-agent-alert field-agent-alert-error"
             role="alert">

            <span>!</span>

            {{ session('error') }}

        </div>

    @endif


    {{-- =====================================================
         KPI CARDS
    ====================================================== --}}
    <section class="field-agent-kpi-grid">


        {{-- Total --}}
        <article class="field-agent-kpi-card">

            <div class="field-agent-kpi-icon">
                👥
            </div>

            <div class="field-agent-kpi-content">

                <span>Total Field Agents</span>

                <strong>
                    {{ number_format($totalFieldAgents ?? $fieldAgents->total()) }}
                </strong>

                <small>
                    Across all partners
                </small>

            </div>

        </article>


        {{-- Active --}}
        <article class="field-agent-kpi-card">

            <div class="field-agent-kpi-icon">
                ✓
            </div>

            <div class="field-agent-kpi-content">

                <span>Active Agents</span>

                <strong>
                    {{ number_format($activeFieldAgents ?? 0) }}
                </strong>

                <small class="positive">
                    Currently active
                </small>

            </div>

        </article>


        {{-- Inactive --}}
        <article class="field-agent-kpi-card">

            <div class="field-agent-kpi-icon">
                —
            </div>

            <div class="field-agent-kpi-content">

                <span>Inactive Agents</span>

                <strong>
                    {{ number_format($inactiveFieldAgents ?? 0) }}
                </strong>

                <small>
                    Currently inactive
                </small>

            </div>

        </article>


        {{-- Unassigned --}}
        <article class="field-agent-kpi-card field-agent-kpi-card-warning">

            <div class="field-agent-kpi-icon">
                !
            </div>

            <div class="field-agent-kpi-content">

                <span>Unassigned</span>

                <strong>
                    {{ number_format($unassignedFieldAgents ?? 0) }}
                </strong>

                <small>
                    Without placement partner
                </small>

            </div>

        </article>

    </section>


    {{-- =====================================================
         SECONDARY STATS
    ====================================================== --}}
    <section class="field-agent-secondary-stats">

        <div>
            <span>Today</span>

            <strong>
                {{ number_format($todayFieldAgents ?? 0) }}
            </strong>
        </div>


        <div>
            <span>Active</span>

            <strong>
                {{ number_format($activeFieldAgents ?? 0) }}
            </strong>
        </div>


        <div>
            <span>With Partner</span>

            <strong>
                {{ number_format($assignedPartnerAgents ?? 0) }}
            </strong>
        </div>


        <div>
            <span>With Center</span>

            <strong>
                {{ number_format($assignedCenterAgents ?? 0) }}
            </strong>
        </div>

    </section>


    {{-- =====================================================
         ANALYTICS
    ====================================================== --}}
    <section class="field-agent-analytics-grid">


        {{-- Registration Activity --}}
        <div class="field-agent-analytics-card">

            <div class="field-agent-card-heading">

                <div>

                    <span>
                        AGENT ACTIVITY
                    </span>

                    <h2>
                        Last 7 Days
                    </h2>

                </div>

                <div class="field-agent-card-total">
                    {{ number_format($lastSevenDays ?? 0) }}
                </div>

            </div>


            @php
                $agentTrend = $registrationTrend ?? collect();

                $maxTrend = max(
                    1,
                    $agentTrend->max('total') ?? 0
                );
            @endphp


            <div class="field-agent-chart">

                @forelse($agentTrend as $item)

                    <div class="field-agent-chart-column">

                        <div class="field-agent-chart-value">
                            {{ $item['total'] ?? 0 }}
                        </div>

                        <div class="field-agent-chart-track">

                            <div class="field-agent-chart-bar"
                                 style="height: {{ max(
                                     5,
                                     (($item['total'] ?? 0) / $maxTrend) * 100
                                 ) }}%;">
                            </div>

                        </div>

                        <span>
                            {{ $item['label'] ?? '' }}
                        </span>

                    </div>

                @empty

                    <div class="field-agent-chart-empty">
                        No activity data available.
                    </div>

                @endforelse

            </div>

        </div>


        {{-- Assignment Overview --}}
        <div class="field-agent-analytics-card field-agent-progress-card">

            <div class="field-agent-card-heading">

                <div>

                    <span>
                        ASSIGNMENT STATUS
                    </span>

                    <h2>
                        Placement Overview
                    </h2>

                </div>

            </div>


            @php

                $totalAgents =
                    $totalFieldAgents
                    ?? $fieldAgents->total()
                    ?? 0;

                $assignedAgents =
                    $assignedPlacementAgents
                    ?? max(
                        0,
                        $totalAgents - ($unassignedFieldAgents ?? 0)
                    );

                $assignmentPercentage =
                    $totalAgents > 0
                    ? round(
                        ($assignedAgents / $totalAgents) * 100,
                        1
                    )
                    : 0;

            @endphp


            <div class="field-agent-progress-circle"
                 style="--completion: {{ $assignmentPercentage * 3.6 }}">

                <div>

                    <strong>
                        {{ $assignmentPercentage }}%
                    </strong>

                    <span>
                        Assigned
                    </span>

                </div>

            </div>


            <div class="field-agent-progress-legend">

                <span>

                    <i class="assigned"></i>

                    Assigned

                    <b>
                        {{ number_format($assignedAgents) }}
                    </b>

                </span>


                <span>

                    <i class="unassigned"></i>

                    Unassigned

                    <b>
                        {{ number_format($unassignedFieldAgents ?? 0) }}
                    </b>

                </span>

            </div>

        </div>

    </section>


    {{-- =====================================================
         EXPORT HISTORY
    ====================================================== --}}
    @if(view()->exists('admin.field_agents.export_history'))

        @include(
            'admin.field_agents.export_history',
            ['exports' => $exports ?? collect()]
        )

    @endif


    {{-- =====================================================
         FIELD AGENT DIRECTORY
    ====================================================== --}}
    <section class="field-agent-table-panel mt-5">


        {{-- Table Heading --}}
        <div class="field-agent-table-heading">

            <div>

                <span>
                    FIELD AGENT DIRECTORY
                </span>

                <h2>
                    All Field Agents
                </h2>

            </div>


            <span class="field-agent-result-count">

                {{ number_format($fieldAgents->total()) }}

                matching records

            </span>

        </div>


        {{-- =================================================
             FILTERS
        ================================================== --}}
        <form method="GET"
              action="{{ route('admin.field-agents.index') }}"
              class="field-agent-filters">


            {{-- Search --}}
            <div class="field-agent-search">

                <span>
                    ⌕
                </span>

                <input type="search"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search name, email, CSC or Sakhi ID...">

            </div>


            {{-- Partner --}}
            <select name="partner_id">

                <option value="">
                    All Partners
                </option>

                @foreach($partners ?? [] as $partner)

                    <option value="{{ $partner->id }}"
                        @selected(
                            (string)($filters['partner_id'] ?? '')
                            ===
                            (string)$partner->id
                        )>

                        {{ $partner->name }}

                    </option>

                @endforeach

            </select>


            {{-- Partner Center --}}
            <select name="partner_center_id">

                <option value="">
                    All Centers
                </option>

                @foreach($partnerCenters ?? [] as $center)

                    <option value="{{ $center->id }}"
                        @selected(
                            (string)($filters['partner_center_id'] ?? '')
                            ===
                            (string)$center->id
                        )>

                        {{ $center->name }}

                    </option>

                @endforeach

            </select>


            {{-- Placement Partner --}}
            <select name="placement_partner_user_id">

                <option value="">
                    All Placement Partners
                </option>

                @foreach($placementPartners ?? [] as $placementPartner)

                    <option value="{{ $placementPartner->id }}"
                        @selected(
                            (string)(
                                $filters['placement_partner_user_id'] ?? ''
                            )
                            ===
                            (string)$placementPartner->id
                        )>

                        {{ $placementPartner->partner_placement_name
                            ?? $placementPartner->name
                            ?? 'Placement Partner' }}

                    </option>

                @endforeach

            </select>


            {{-- Status --}}
            <select name="status">

                <option value="">
                    All Status
                </option>

                <option value="active"
                    @selected(
                        ($filters['status'] ?? '') === 'active'
                    )>

                    Active

                </option>

                <option value="inactive"
                    @selected(
                        ($filters['status'] ?? '') === 'inactive'
                    )>

                    Inactive

                </option>

            </select>


            {{-- Location --}}
            <input type="text"
                   name="district"
                   value="{{ $filters['district'] ?? '' }}"
                   placeholder="District">


            {{-- Filter Actions --}}
            <div class="field-agent-filter-actions">

                <button type="submit"
                        class="field-agent-btn field-agent-btn-primary">

                    Apply Filters

                </button>


                <a href="{{ route('admin.field-agents.index') }}"
                   class="field-agent-clear-btn">

                    Clear

                </a>

            </div>

        </form>


        {{-- =================================================
             TABLE
        ================================================== --}}
        <div class="field-agent-table-wrap">

            <table class="field-agent-table">

                <thead>

                    <tr>

                        <th>
                            Agent
                        </th>

                        <th>
                            CSC / Sakhi ID
                        </th>

                        <th>
                            Partner
                        </th>

                        <th>
                            Contact
                        </th>

                        <th>
                            Placement Partner
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($fieldAgents as $fieldAgent)

                        <tr>


                            {{-- Agent --}}
                            <td>

                                <div class="field-agent-person">

                                    <div class="field-agent-avatar">

                                        {{ strtoupper(
                                            substr(
                                                trim($fieldAgent->name ?? 'A'),
                                                0,
                                                1
                                            )
                                        ) }}

                                    </div>


                                    <div>

                                        <strong>
                                            {{ $fieldAgent->name ?: '—' }}
                                        </strong>

                                        <small>
                                            {{ $fieldAgent->email ?: 'Email not available' }}
                                        </small>

                                    </div>

                                </div>

                            </td>


                            {{-- CSC / Sakhi --}}
                            <td>

                                <div class="field-agent-id">

                                    <strong>
                                        {{ $fieldAgent->csc_id ?: '—' }}
                                    </strong>

                                    <small>
                                        {{ $fieldAgent->sakhi_id ?: '—' }}
                                    </small>

                                </div>

                            </td>


                            {{-- Partner --}}
                            <td>

                                <div class="field-agent-partner">

                                    <strong>
                                        {{ $fieldAgent->partner?->name ?: '—' }}
                                    </strong>

                                    @if($fieldAgent->partnerCenter)

                                        <small>
                                            {{ $fieldAgent->partnerCenter->name }}
                                        </small>

                                    @endif

                                </div>

                            </td>


                            {{-- Contact --}}
                            <td>

                                <span class="field-agent-mobile">

                                    {{ $fieldAgent->contact_number ?: '—' }}

                                </span>

                            </td>


                            {{-- Placement Partner --}}
                            <td>

                                @if($fieldAgent->placementPartnerUser)

                                    <div class="field-agent-placement">

                                        <strong>

                                            {{
                                                $fieldAgent
                                                    ->placementPartnerUser
                                                    ->partner_placement_name
                                                ?: '—'
                                            }}

                                        </strong>

                                    </div>

                                @else

                                    <span class="field-agent-unassigned">
                                        Unassigned
                                    </span>

                                @endif

                            </td>


                            {{-- Location --}}
                            <td>

                                <div class="field-agent-location">

                                    <strong>
                                        {{ $fieldAgent->district ?: '—' }}
                                    </strong>

                                    @if($fieldAgent->state)

                                        <small>
                                            {{ $fieldAgent->state }}
                                        </small>

                                    @endif

                                </div>

                            </td>


                            {{-- Status --}}
                            <td>

                                <span class="field-agent-status
                                    {{
                                        $fieldAgent->status === 'active'
                                            ? 'field-agent-status-active'
                                            : 'field-agent-status-inactive'
                                    }}">

                                    ●

                                    {{ ucfirst($fieldAgent->status ?? 'inactive') }}

                                </span>

                            </td>


                            {{-- Actions --}}
                            <td>

                                <div class="field-agent-actions">


                                    @can('field_agents.update')

                                        <a href="{{ route(
                                            'admin.field-agents.edit',
                                            $fieldAgent
                                        ) }}"
                                           title="Edit">

                                            Edit

                                        </a>

                                    @endcan


                                    @can('field_agents.delete')

                                        <form method="POST"
                                              action="{{ route(
                                                  'admin.field-agents.destroy',
                                                  $fieldAgent
                                              ) }}"
                                              onsubmit="return confirm('Delete this field agent?')">

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

                            <td colspan="8"
                                class="field-agent-empty">

                                <div>

                                    <strong>
                                        No field agents found
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


        {{-- =================================================
             PAGINATION
        ================================================== --}}
        <footer class="field-agent-table-footer">

            <span>

                Showing

                <strong>
                    {{ $fieldAgents->firstItem() ?? 0 }}
                </strong>

                –

                <strong>
                    {{ $fieldAgents->lastItem() ?? 0 }}
                </strong>

                of

                <strong>
                    {{ number_format($fieldAgents->total()) }}
                </strong>

            </span>


            <div>

                {{ $fieldAgents->onEachSide(1)->links() }}

            </div>

        </footer>

    </section>

</section>

</main>

@endsection
