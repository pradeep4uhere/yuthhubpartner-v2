@extends('layouts.app')

@section('content')
    <main class="portal-dashboard">
        <header class="portal-header">
            <a class="portal-brand" href="{{ route('partner-center.dashboard') }}">
                <span class="portal-brand__mark">Y</span>
                <span>YouthHub <strong>Partner</strong></span>
            </a>
            <span class="portal-header__label">CENTER WORKSPACE</span>
            <form method="POST" action="{{ route('partner-center.logout') }}">
                @csrf
                <button class="portal-signout" type="submit">Sign out</button>
            </form>
        </header>

        <section class="portal-content">
            <p class="admin-auth-eyebrow">PARTNER CENTER</p>
            <h1>Welcome, {{ $partnerCenter->name }}</h1>
            <p class="portal-intro">Your center workspace and parent partner information.</p>

            <div class="portal-summary-grid">
                <article class="portal-summary">
                    <span>Center code</span>
                    <strong>{{ $partnerCenter->partner_code }}</strong>
                </article>
                <article class="portal-summary">
                    <span>Account status</span>
                    <strong class="portal-active">{{ ucfirst($partnerCenter->status) }}</strong>
                </article>
                <article class="portal-summary">
                    <span>Parent partner</span>
                    <strong>{{ $partnerCenter->partner->name }}</strong>
                </article>
            </div>

            <section class="portal-section">
                <div class="portal-section__heading"><div><h2>Organisation contact</h2><p>Parent partner account details.</p></div></div>
                <dl class="portal-contact-list">
                    <div><dt>Partner name</dt><dd>{{ $partnerCenter->partner->name }}</dd></div>
                    <div><dt>Partner code</dt><dd>{{ $partnerCenter->partner->partner_code }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $partnerCenter->partner->email }}</dd></div>
                    <div><dt>Contact number</dt><dd>{{ $partnerCenter->partner->contact_number ?: 'Not provided' }}</dd></div>
                </dl>
            </section>
        </section>
    </main>
@endsection
