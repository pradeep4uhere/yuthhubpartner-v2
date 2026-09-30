@extends('layouts.app')

@section('content')
    <main class="portal-login-page">
        <section class="portal-login-panel" aria-labelledby="portal-login-title">
            <a class="portal-brand" href="{{ route('home') }}" aria-label="YouthHub Partner home">
                <span class="portal-brand__mark">
                     <img src="../icon.png" alt="YouthHub Partner" />
                </span>
                <span>YouthHub <strong>Partner</strong></span>
            </a>

            <div class="portal-login-heading">
                <p class="admin-auth-eyebrow">{{ strtoupper($portalName) }} PORTAL</p>
                <h1 id="portal-login-title">Welcome back</h1>
                <p>{{ $portalDescription }}</p>
            </div>

            @if ($errors->any())
                <div class="admin-auth-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ $loginAction }}" class="admin-auth-form">
                @csrf
                <label for="email">Field Agent ID</label>
                <input id="email" name="sakhi_id" type="sakhi_id" value="{{ old('sakhi_id') }}" autocomplete="username" required autofocus>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <button type="submit">Sign in to {{ $portalName }}</button>
            </form>

            <p class="admin-auth-footnote">Contact your organisation administrator if you need account access.</p>
        </section>
        <aside class="portal-login-aside" aria-label="YouthHub Partner portal">
            <span>YOUTHHUB / {{ strtoupper($portalName) }}</span>
            <div>
                <p>PARTNER NETWORK</p>
                <h2>Better connected. Greater opportunity.</h2>
            </div>
            <small>Secure partner workspace | Email: support@yuthhubpartner.org</small>
        </aside>
    </main>
@endsection
