@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.dashboard') }}">&larr; Dashboard</a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-content">

            <div class="admin-partners-heading">
                <div>
                    <p class="admin-auth-eyebrow">LEARNER MANAGEMENT</p>
                    <h1>Learners</h1>
                    <p class="admin-users-intro">
                        Manage learners, course status and field agent assignments.
                    </p>
                </div>

                @can('learners.create')
                    <a class="admin-partners-primary"
                        href="{{ route('admin.learners.create') }}">
                        Add learner
                    </a>
                @endcan
            </div>

            @if (session('status'))
                <div class="admin-users-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <section class="admin-partners-panel">

                <form method="GET"
                    action="{{ route('admin.learners.index') }}"
                    class="admin-partners-filters">

                    <label class="admin-partners-search">
                        <span class="visually-hidden">Search learners</span>

                        <input type="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search learner name, mobile, program code">
                    </label>

                    <label>
                        <span class="visually-hidden">Partner</span>

                        <select name="partner_id">
                            <option value="">All partners</option>

                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}"
                                    @selected((string) ($filters['partner_id'] ?? '') === (string) $partner->id)>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="visually-hidden">Course Status</span>

                        <select name="course_completed">
                            <option value="">Course Status</option>

                            <option value="1"
                                @selected(($filters['course_completed'] ?? '') === '1')>
                                Completed
                            </option>

                            <option value="0"
                                @selected(($filters['course_completed'] ?? '') === '0')>
                                Not Completed
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="visually-hidden">Status</span>

                        <select name="status">
                            <option value="">All statuses</option>

                            <option value="active"
                                @selected(($filters['status'] ?? '') === 'active')>
                                Active
                            </option>

                            <option value="inactive"
                                @selected(($filters['status'] ?? '') === 'inactive')>
                                Inactive
                            </option>
                        </select>
                    </label>

                    <button type="submit">Apply</button>

                    @if (! empty($filters))
                        <a class="admin-partners-reset"
                            href="{{ route('admin.learners.index') }}">
                            Clear
                        </a>
                    @endif
                </form>

                <div class="admin-partners-table-wrap">

                    <table class="admin-partners-table">

                        <thead>
                            <tr>
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

                            @forelse ($learners as $learner)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ trim($learner->first_name . ' ' . $learner->last_name) }}
                                        </strong>

                                        <br>

                                        <small>
                                            {{ $learner->gender ?: '—' }}
                                        </small>
                                    </td>

                                    <td>
                                        {{ $learner->normalized_mobile ?: $learner->phone_number }}
                                    </td>

                                    <td>
                                        {{ $learner->program_code ?: '—' }}
                                    </td>

                                    <td>
                                        <strong>
                                            {{ $learner->partner?->name }}
                                        </strong>

                                        <br>

                                        <small>
                                            {{ $learner->partnerCenter?->name }}
                                        </small>
                                    </td>

                                    <td>
                                        @if($learner->fieldAgent)
                                            {{ $learner->fieldAgent->name }}

                                            <br>

                                            <small>
                                                {{ $learner->fieldAgent->sakhi_id }}
                                            </small>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td>
                                        {{ $learner->city }}

                                        @if($learner->state)
                                            <br>
                                            <small>{{ $learner->state }}</small>
                                        @endif
                                    </td>

                                    <td>
                                        @if($learner->course_completed)
                                            <span class="admin-partner-status admin-partner-status--active">
                                                Completed
                                            </span>
                                        @else
                                            <span class="admin-partner-status admin-partner-status--inactive">
                                                Pending
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="admin-partner-status admin-partner-status--{{ $learner->status }}">
                                            {{ ucfirst($learner->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="admin-partner-actions">

                                            @can('learners.update')
                                                <a href="{{ route('admin.learners.edit', $learner) }}">
                                                    Edit
                                                </a>
                                            @endcan

                                            @can('learners.delete')
                                                <form method="POST"
                                                    action="{{ route('admin.learners.destroy', $learner) }}"
                                                    onsubmit="return confirm('Delete this learner?')">

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
                                    <td class="admin-partners-empty" colspan="9">
                                        No learners found. Add a learner to get started.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <footer class="admin-partners-footer">

                    <span>
                        Showing
                        {{ $learners->firstItem() ?? 0 }}
                        –
                        {{ $learners->lastItem() ?? 0 }}
                        of
                        {{ $learners->total() }}
                        learners
                    </span>

                    {{ $learners->links() }}

                </footer>

            </section>

        </section>
    </main>
@endsection
