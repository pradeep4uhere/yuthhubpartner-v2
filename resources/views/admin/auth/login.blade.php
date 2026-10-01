@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
<main class="yh-admin-login">

    {{-- =====================================================
         LEFT: LOGIN SECTION
    ====================================================== --}}
    <section class="yh-login-section">

        <div class="yh-login-card">

            {{-- Brand --}}
            <a class="yh-brand"
               href="{{ route('home') }}"
               aria-label="YouthHub Partner home">

                <span class="yh-brand-logo">
                    <img src="{{ asset('icon.png') }}"
                         alt="YouthHub Partner">
                </span>

                <span class="yh-brand-name">
                    YouthHub
                    <strong>Partner</strong>
                </span>

            </a>


            {{-- Heading --}}
            <div class="yh-login-heading">

                <div class="yh-admin-badge">
                    <span class="yh-badge-dot"></span>
                    ADMINISTRATION PORTAL
                </div>

                <h1>
                    Welcome <span>Back</span>
                </h1>

                <p>
                    Sign in to securely manage your YouthHub Partner workspace.
                </p>

            </div>


            {{-- Validation Error --}}
            @if ($errors->any())
                <div class="yh-login-error" role="alert">
                    <span class="yh-error-icon">!</span>

                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>
            @endif


            {{-- Login Form --}}
            <form method="POST"
                  action="{{ route('admin.login.store') }}"
                  class="yh-login-form">

                @csrf


                {{-- Email --}}
                <div class="yh-form-group">

                    <label for="email">
                        Email address
                    </label>

                    <div class="yh-input-wrapper">

                        <span class="yh-input-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M4 6h16v12H4z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"/>

                                <path
                                    d="m4 7 8 6 8-6"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"/>
                            </svg>
                        </span>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email address"
                            autocomplete="username"
                            required
                            autofocus
                        >

                    </div>

                </div>


                {{-- Password --}}
                <div class="yh-form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="yh-input-wrapper">

                        <span class="yh-input-icon">

                            <svg viewBox="0 0 24 24" fill="none">

                                <rect
                                    x="5"
                                    y="10"
                                    width="14"
                                    height="10"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.7"/>

                                <path
                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                    stroke="currentColor"
                                    stroke-width="1.7"/>

                                <circle
                                    cx="12"
                                    cy="15"
                                    r="1"
                                    fill="currentColor"/>

                            </svg>

                        </span>


                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="yh-password-toggle"
                            id="yhPasswordToggle"
                            aria-label="Show password">

                            <svg viewBox="0 0 24 24" fill="none">

                                <path
                                    d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"/>

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="2.5"
                                    stroke="currentColor"
                                    stroke-width="1.7"/>

                            </svg>

                        </button>

                    </div>

                </div>


                {{-- Remember Me --}}
                <div class="yh-form-options">

                    <label class="yh-remember">

                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            value="1">

                        <span class="yh-custom-checkbox"></span>

                        <span>
                            Keep me signed in
                        </span>

                    </label>

                </div>


                {{-- Submit --}}
                <button
                    type="submit"
                    class="yh-login-button"
                    id="yhLoginButton">

                    <span class="yh-button-shield">

                        <svg viewBox="0 0 24 24" fill="none">

                            <path
                                d="M12 3 19 6v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"/>

                            <path
                                d="m9 12 2 2 4-4"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"/>

                        </svg>

                    </span>

                    <span class="yh-button-text">
                        Sign in to admin
                    </span>

                    <span class="yh-button-arrow">
                        →
                    </span>

                </button>

            </form>


            {{-- Security Notice --}}
            <div class="yh-security-notice">

                <div class="yh-security-icon">

                    <svg viewBox="0 0 24 24" fill="none">

                        <rect
                            x="5"
                            y="10"
                            width="14"
                            height="10"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.6"/>

                        <path
                            d="M8 10V7a4 4 0 0 1 8 0v3"
                            stroke="currentColor"
                            stroke-width="1.6"/>

                    </svg>

                </div>

                <div>

                    <strong>
                        Protected access
                    </strong>

                    <span>
                        Access is limited to invited administrators.
                    </span>

                </div>

            </div>


            {{-- Footer --}}
            <div class="yh-login-footer">

                <span>
                    © {{ date('Y') }} YouthHub Partner
                </span>

                <span class="yh-footer-separator">
                    •
                </span>

                <span>
                     Secure Access. Protected Operations.
                </span>

            </div>

        </div>

    </section>


    {{-- =====================================================
         RIGHT: SECURITY PANEL
    ====================================================== --}}
    <aside class="yh-security-panel"
           aria-label="YouthHub Partner secure administration portal">

        <div class="yh-security-glow yh-security-glow-one"></div>
        <div class="yh-security-glow yh-security-glow-two"></div>


        {{-- Top --}}
        <div class="yh-security-top">

            <span class="yh-portal-code">
                YH / ADMIN
            </span>

            <span class="yh-live-status">

                <span></span>

                SECURE ENVIRONMENT

            </span>

        </div>


        {{-- Main --}}
        <div class="yh-security-content">

            <div class="yh-security-label">
                ADMIN PORTAL
            </div>


            <h2>
                Secure Access.
                <br>
                <span>Protected Operations.</span>
            </h2>


            <p>
                Manage partners, learners and programs
                through a secure administrative workspace.
            </p>


           {{-- Shield --}}
<div class="yh-shield-container">

    <div class="yh-shield-ring yh-shield-ring-one"></div>
    <div class="yh-shield-ring yh-shield-ring-two"></div>

    <div class="yh-shield">

        <svg viewBox="0 0 120 140" fill="none">

            <path
                d="M60 7 106 25v34c0 31-19 58-46 70C33 117 14 90 14 59V25L60 7Z"
                stroke="currentColor"
                stroke-width="2"/>

            <path
                d="M60 20 94 34v25c0 24-14 44-34 55-20-11-34-31-34-55V34l34-14Z"
                stroke="currentColor"
                stroke-width="1.5"
                opacity=".55"/>

            <rect
                x="42"
                y="60"
                width="36"
                height="29"
                rx="5"
                fill="currentColor"
                opacity=".9"/>

            <path
                d="M49 60v-7a11 11 0 0 1 22 0v7"
                stroke="currentColor"
                stroke-width="6"
                stroke-linecap="round"/>

            <circle
                cx="60"
                cy="73"
                r="3"
                fill="#08244A"/>

            <path
                d="M60 76v6"
                stroke="#08244A"
                stroke-width="2"
                stroke-linecap="round"/>

        </svg>

    </div>

    {{-- Power landing effect --}}
    <div class="yh-power-impact">
        <span></span>
        <span></span>
        <span></span>
    </div>

</div>


            {{-- Features --}}
            <div class="yh-security-features">

                <div class="yh-feature">

                    <div class="yh-feature-icon">

                        <svg viewBox="0 0 24 24" fill="none">

                            <path
                                d="M12 3 19 6v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3Z"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                            <path
                                d="m9 12 2 2 4-4"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                        </svg>

                    </div>

                    <span>
                        Secure<br>
                        Login
                    </span>

                </div>


                <div class="yh-feature">

                    <div class="yh-feature-icon">

                        <svg viewBox="0 0 24 24" fill="none">

                            <circle
                                cx="9"
                                cy="8"
                                r="3"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                            <circle
                                cx="17"
                                cy="9"
                                r="2"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                            <path
                                d="M3 19c0-3 2.5-5 6-5s6 2 6 5"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                            <path
                                d="M15 15c3 0 5 1.5 5 4"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                        </svg>

                    </div>

                    <span>
                        Manage<br>
                        Partners
                    </span>

                </div>


                <div class="yh-feature">

                    <div class="yh-feature-icon">

                        <svg viewBox="0 0 24 24" fill="none">

                            <path
                                d="M4 19V9M10 19V5M16 19v-8M22 19V3"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                            <path
                                d="M3 19h20"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                        </svg>

                    </div>

                    <span>
                        Track<br>
                        Progress
                    </span>

                </div>


                <div class="yh-feature">

                    <div class="yh-feature-icon">

                        <svg viewBox="0 0 24 24" fill="none">

                            <path
                                d="M12 3 19 6v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3Z"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                            <path
                                d="m8.5 12 2.2 2.2 4.8-5"
                                stroke="currentColor"
                                stroke-width="1.7"/>

                        </svg>

                    </div>

                    <span>
                        Build<br>
                        Opportunities
                    </span>

                </div>

            </div>

        </div>


        {{-- Bottom --}}
        <div class="yh-security-bottom">

            <div class="yh-bottom-brand">

                <strong>
                    YouthHub Partner
                </strong>

                <span>
                    Secure administrative workspace
                </span>

            </div>


            <div class="yh-bottom-message">

                <span>
                    EMPOWERING YOUTH
                </span>

                <span>
                    BUILDING TOMORROW
                </span>

            </div>

        </div>

    </aside>

</main>


{{-- =====================================================
     PASSWORD TOGGLE + LOGIN LOADING
====================================================== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const passwordInput =
        document.getElementById('password');

    const passwordToggle =
        document.getElementById('yhPasswordToggle');

    const loginForm =
        document.querySelector('.yh-login-form');

    const loginButton =
        document.getElementById('yhLoginButton');

    const buttonText =
        loginButton?.querySelector('.yh-button-text');


    /*
    |--------------------------------------------------------------------------
    | Password Show / Hide
    |--------------------------------------------------------------------------
    */

    if (passwordToggle && passwordInput) {

        passwordToggle.addEventListener('click', function () {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword ? 'text' : 'password';

            this.setAttribute(
                'aria-label',
                isPassword
                    ? 'Hide password'
                    : 'Show password'
            );

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Login Loading State
    |--------------------------------------------------------------------------
    */

    if (loginForm && loginButton) {

        loginForm.addEventListener('submit', function () {

            loginButton.classList.add('is-loading');

            if (buttonText) {
                buttonText.textContent =
                    'Authenticating...';
            }

        });

    }

});
</script>
@endpush
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
@endpush
@endsection
