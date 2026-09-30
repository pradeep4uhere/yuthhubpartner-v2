@extends('layouts.app')

@section('content')
    <main class="portal-dashboard">
        <header class="portal-header">
            <a class="portal-brand" href="{{ route('partner.dashboard') }}">
                <span class="portal-brand__mark">Y</span>
                <span>YouthHub <strong>Partner</strong></span>
            </a>
            <span class="portal-header__label">PARTNER WORKSPACE</span>
            <form method="POST" action="{{ route('partner.logout') }}">
                @csrf
                <button class="portal-signout" type="submit">Sign out</button>
            </form>
        </header>

        <section class="portal-content">
            <p class="admin-auth-eyebrow">PARTNER OVERVIEW</p>
            <h1>Welcome, {{ $partner->name }}</h1>
            <p class="portal-intro">Your organisation workspace and connected centers.</p>

            <div class="portal-summary-grid">
                <article class="portal-summary">
                    <span>Partner code</span>
                    <strong>{{ $partner->partner_code }}</strong>
                </article>
                <article class="portal-summary">
                    <span>Account status</span>
                    <strong class="portal-active">{{ ucfirst($partner->status) }}</strong>
                </article>
                <article class="portal-summary">
                    <span>Partner centers</span>
                    <strong>{{ $partner->centers->count() }}</strong>
                </article>
            </div>

            <section class="portal-section">
                <div class="portal-section__heading">
                    <div><h2>Your centers</h2><p>Centers linked to this partner account.</p></div>
                    <span>{{ $partner->centers->count() }} total</span>
                </div>
                <div class="portal-center-list">
                    @forelse ($partner->centers as $center)
                        <article class="portal-center-row">
                            <div><strong>{{ $center->name }}</strong><small>{{ $center->partner_code }}</small></div>
                            <span class="admin-partner-status admin-partner-status--{{ $center->status }}">{{ ucfirst($center->status) }}</span>
                        </article>
                    @empty
                        <p class="portal-empty">No centers are linked to this partner yet.</p>
                    @endforelse
                </div>
            </section>
        </section>
    </main>
@endsection
