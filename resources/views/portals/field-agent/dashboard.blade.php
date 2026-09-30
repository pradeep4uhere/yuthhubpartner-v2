@extends('layouts.app')

@section('content')
    <main class="field-agent-app">
        <header class="field-agent-header">
            <a class="portal-brand" href="{{ route('field-agent.dashboard') }}" aria-label="Field Agent home">
                <span class="portal-brand__mark">Y</span>
                <span>YouthHub <strong>Field</strong></span>
            </a>
            <span class="field-agent-header__status"><i></i> {{ ucfirst($fieldAgent->status) }}</span>
            <form method="POST" action="{{ route('field-agent.logout') }}">
                @csrf
                <button class="field-agent-signout" type="submit">Sign out</button>
            </form>
        </header>

        <section class="field-agent-main">
            <div class="field-agent-welcome">
                <p class="field-agent-eyebrow">FIELD AGENT WORKSPACE</p>
                <h1>Hello, {{ $fieldAgent->name }}</h1>
                <p>Your assigned organisations and area are ready below.</p>
            </div>

            <section class="field-agent-assignment" aria-labelledby="assignment-heading">
                <div class="field-agent-section-heading">
                    <span class="field-agent-section-icon" aria-hidden="true">⌖</span>
                    <div><h2 id="assignment-heading">My assignment</h2><p>Your current field coverage</p></div>
                </div>
                <div class="field-agent-assignment-grid">
                    <article><span>Partner</span><strong>{{ $fieldAgent->partner->name }}</strong><small>{{ $fieldAgent->partner->partner_code }}</small></article>
                    <article><span>Partner center</span><strong>{{ $fieldAgent->partnerCenter->name }}</strong><small>{{ $fieldAgent->partnerCenter->partner_code }}</small></article>
                    <article><span>Placement partner</span><strong>{{ $fieldAgent->placementPartnerUser->partner_placement_name }}</strong><small>{{ $fieldAgent->placementPartnerUser->pp_code }}</small></article>
                </div>
            </section>

            <section class="field-agent-profile" aria-labelledby="profile-heading">
                <div class="field-agent-section-heading">
                    <span class="field-agent-section-icon field-agent-section-icon--coral" aria-hidden="true">ID</span>
                    <div><h2 id="profile-heading">My details</h2><p>Your field-agent profile</p></div>
                </div>
                <dl class="field-agent-details">
                    <div><dt>Email</dt><dd>{{ $fieldAgent->email }}</dd></div>
                    <div><dt>Contact number</dt><dd>{{ $fieldAgent->contact_number ?: 'Not provided' }}</dd></div>
                    <div><dt>CSC ID</dt><dd>{{ $fieldAgent->csc_id ?: 'Not provided' }}</dd></div>
                    <div><dt>Sakhi ID</dt><dd>{{ $fieldAgent->sakhi_id ?: 'Not provided' }}</dd></div>
                    <div><dt>State</dt><dd>{{ $fieldAgent->state }}</dd></div>
                    <div><dt>District</dt><dd>{{ $fieldAgent->district }}</dd></div>
                </dl>
            </section>

            <footer class="field-agent-footer">YOUTHHUB PARTNER <span>Field operations</span></footer>
        </section>
    </main>
@endsection
