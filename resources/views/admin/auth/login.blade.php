@extends('layouts.app')

@section('content')
    <main class="admin-auth-page">
        <section class="admin-auth-panel" aria-labelledby="admin-login-title">
            <a class="admin-auth-brand" href="{{ route('home') }}" aria-label="YouthHub Partner home">
                <span class="admin-auth-brand__mark">
                     <img src="../icon.png" alt="YouthHub Partner" />
                </span>
                <span>YouthHub <strong>Partner</strong></span>
            </a>

            <div class="admin-auth-heading">
                <p class="admin-auth-eyebrow">ADMINISTRATION</p>
                <h1 id="admin-login-title">Welcome back</h1>
                <p>Sign in to manage the YouthHub Partner workspace.</p>
            </div>

            @if ($errors->any())
                <div class="admin-auth-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="admin-auth-form">
                @csrf
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <label class="admin-auth-remember" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1">
                    <span>Keep me signed in</span>
                </label>

                <button type="submit">Sign in to admin</button>
            </form>

            <p class="admin-auth-footnote">Access is limited to invited administrators.</p>
        </section>
        <aside class="admin-auth-aside" aria-label="YouthHub Partner administration">
            <span class="admin-auth-aside__index">YH / ADMIN</span>
            <div>
                <p>PARTNER OPERATIONS</p>
                <h2>Make every opportunity count.</h2>
            </div>
            <span class="admin-auth-aside__footer">YouthHub Partner · Secure workspace</span>
        </aside>
    </main>
@endsection
