@extends('layouts.app')

@section('content')
    <main class="portal-dashboard">
        <header class="portal-header">
            <a class="portal-brand" href="{{ route('partner-placement.dashboard') }}">
                <span class="portal-brand__mark">Y</span>
                <span>YouthHub <strong>Partner</strong></span>
            </a>
            <span class="portal-header__label">PLACEMENT WORKSPACE</span>
            <form method="POST" action="{{ route('partner-placement.logout') }}">
                @csrf
                <button class="portal-signout" type="submit">Sign out</button>
            </form>
        </header>

        <section class="portal-content">
            <p class="admin-auth-eyebrow">PLACEMENT PARTNER</p>
            <h1>Welcome, {{ $placementUser->name }}</h1>
            <p class="portal-intro">Your placement organisation workspace.</p>

            <div class="portal-summary-grid">
                <article class="portal-summary"><span>Placement organisation</span><strong>{{ $placementUser->partner_placement_name }}</strong></article>
                <article class="portal-summary"><span>Placement code</span><strong>{{ $placementUser->pp_code }}</strong></article>
                <article class="portal-summary"><span>Account status</span><strong class="portal-active">{{ ucfirst($placementUser->status) }}</strong></article>
            </div>

            <section class="portal-section">
                <div class="portal-section__heading"><div><h2>Account contact</h2><p>Placement organisation account details.</p></div></div>
                <dl class="portal-contact-list">
                    <div><dt>Contact name</dt><dd>{{ $placementUser->name }}</dd></div>
                    <div><dt>Login email</dt><dd>{{ $placementUser->email }}</dd></div>
                    <div><dt>Contact number</dt><dd>{{ $placementUser->contact_number ?: 'Not provided' }}</dd></div>
                    <div><dt>Placement organisation</dt><dd>{{ $placementUser->partner_placement_name }}</dd></div>
                </dl>
            </section>
        </section>
    </main>
@endsection
