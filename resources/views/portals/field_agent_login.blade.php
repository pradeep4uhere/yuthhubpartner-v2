
@extends('layouts.app')

@section('content')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    .fa-login,
    .fa-login * {
        box-sizing: border-box;
    }

    .fa-login {
        --primary: #0866d5;
        --primary-dark: #064da9;
        --navy: #092b5c;
        --muted: #627795;

        min-height: 100vh;
        display: grid;
        grid-template-columns: 45% 55%;
        overflow: hidden;
        background: #edf4ff;
        font-family: 'Inter', sans-serif;
        color: var(--navy);
    }

    /* LEFT LOGIN PANEL */

    .fa-login__left {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        padding: 48px clamp(30px, 5.2vw, 82px);
        background: linear-gradient(145deg, #fff 0%, #fbfcff 100%);
        border-radius: 0 55px 55px 0;
        box-shadow: 12px 0 40px rgba(19, 57, 105, .07);
    }

    .fa-login__brand {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 42px;
        color: var(--navy);
        text-decoration: none;
        font-size: clamp(25px, 2.2vw, 36px);
        font-weight: 800;
        letter-spacing: -1.4px;
    }

    .fa-login__brand img {
        display: block;
        width: 57px;
        height: 60px;
        object-fit: contain;
    }

    .fa-login__brand strong {
        color: var(--primary);
    }

    .fa-login__badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin-bottom: 17px;
        padding: 8px 13px;
        border-radius: 8px;
        background: #e8f1ff;
        color: #075bc2;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .fa-login__heading {
        margin-bottom: 30px;
    }

    .fa-login__heading h1 {
        margin: 0 0 14px;
        color: #0a2855;
        font-size: clamp(34px, 3vw, 46px);
        font-weight: 800;
        line-height: 1.18;
        letter-spacing: -1.5px;
    }

    .fa-login__heading p {
        max-width: 420px;
        margin: 0;
        color: var(--muted);
        font-size: 15px;
        line-height: 1.85;
    }

    /* VALIDATION */

    .fa-login__error {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 22px;
        padding: 14px 15px;
        border: 1px solid #ffb4b4;
        border-radius: 10px;
        background: #fff0f0;
        color: #c62828;
        font-size: 13px;
        line-height: 1.6;
    }

    .fa-login__error i {
        margin-top: 2px;
    }

    /* FORM */

    .fa-login__form {
        width: 100%;
        margin: 0;
    }

    .fa-login__field {
        margin-bottom: 23px;
    }

    .fa-login__field label {
        display: block;
        margin-bottom: 9px;
        color: #29466f;
        font-size: 14px;
        font-weight: 700;
    }

    .fa-login__input-wrap {
        position: relative;
    }

    .fa-login__input-wrap > .fa-field-icon {
        position: absolute;
        top: 50%;
        left: 17px;
        z-index: 1;
        transform: translateY(-50%);
        color: #667d9c;
        font-size: 17px;
        pointer-events: none;
    }

    .fa-login__input {
        display: block;
        width: 100%;
        height: 56px;
        padding: 0 48px 0 49px;
        border: 1px solid #d4e0f0;
        border-radius: 10px;
        outline: none;
        background: #fff;
        color: #18375f;
        font-family: inherit;
        font-size: 14px;
        transition: border-color .2s, box-shadow .2s;
    }

    .fa-login__input::placeholder {
        color: #9aabc3;
    }

    .fa-login__input:hover {
        border-color: #a8c5ed;
    }

    .fa-login__input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(8, 102, 213, .10);
    }

    .fa-login__password-toggle {
        position: absolute;
        top: 50%;
        right: 13px;
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: #607795;
        font-size: 17px;
        cursor: pointer;
    }

    .fa-login__password-toggle:hover {
        background: #edf4ff;
        color: var(--primary);
    }

    .fa-login__submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 13px;
        width: 100%;
        min-height: 57px;
        margin-top: 8px;
        padding: 14px 20px;
        border: 0;
        border-radius: 11px;
        background: linear-gradient(100deg, #0876e5, #0754c5);
        box-shadow: 0 8px 18px rgba(7, 85, 197, .17);
        color: #fff;
        font-family: inherit;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: transform .2s, box-shadow .2s;
    }

    .fa-login__submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 23px rgba(7, 85, 197, .25);
    }

    .fa-login__submit:focus-visible,
    .fa-login__password-toggle:focus-visible {
        outline: 3px solid #80b5ff;
        outline-offset: 3px;
    }

    .fa-login__support {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        margin-top: 25px;
        padding-top: 21px;
        border-top: 1px solid #e5ecf5;
        color: #617693;
        font-size: 12.5px;
        line-height: 1.8;
    }

    .fa-login__support i {
        margin-top: 3px;
        color: #55749d;
        font-size: 20px;
    }

    /* RIGHT HERO PANEL */

    .fa-login__hero {
        position: relative;
        isolation: isolate;
        display: flex;
        align-items: flex-start;
        min-width: 0;
        min-height: 100vh;
        padding: clamp(65px, 10vh, 115px) clamp(28px, 4.5vw, 72px) 45px;
        overflow: hidden;
        background-color: #125a9e;

        /*
         * Upload your image to:
         * public/images/field-agent-background.png
         */
        background-image:
            linear-gradient(
                90deg,
                rgba(4, 57, 112, .73) 0%,
                rgba(4, 67, 126, .45) 43%,
                rgba(4, 67, 126, .04) 100%
            ),
            url("/images/field-agent-background.png");

        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff;
    }

    .fa-login__hero::before {
        content: "";
        position: absolute;
        z-index: -1;
        top: -180px;
        right: -200px;
        width: 570px;
        height: 570px;
        border: 55px solid rgba(255, 255, 255, .09);
        border-radius: 50%;
        pointer-events: none;
    }

    .fa-login__hero::after {
        content: "";
        position: absolute;
        z-index: -1;
        bottom: -180px;
        left: -130px;
        width: 410px;
        height: 310px;
        border-radius: 50%;
        background: rgba(0, 112, 220, .28);
        transform: rotate(-20deg);
        pointer-events: none;
    }

    .fa-login__hero-content {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 490px;
    }

    .fa-login__accent {
        width: 65px;
        height: 5px;
        margin-bottom: 23px;
        border-radius: 10px;
        background: #ffb629;
    }

    .fa-login__hero-eyebrow {
        margin: 0 0 10px;
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 1.5px;
    }

    .fa-login__hero h2 {
        margin: 0;
        color: #ffd24d;
        font-size: clamp(34px, 3.3vw, 51px);
        font-weight: 900;
        line-height: 1.12;
        letter-spacing: -1.3px;
        text-transform: uppercase;
    }

    .fa-login__hero-subtitle {
        margin: 17px 0 0;
        color: #fff;
        font-size: clamp(14px, 1.35vw, 19px);
        line-height: 1.8;
    }

    .fa-login__hero-subtitle span {
        padding: 0 5px;
        color: #ffd24d;
    }

    /* BENEFITS */

    .fa-login__benefits {
        display: grid;
        gap: 22px;
        margin-top: 42px;
    }

    .fa-login__benefit {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .fa-login__benefit-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        flex: 0 0 54px;
        border-radius: 50%;
        background: #5474e9;
        color: #fff;
        font-size: 21px;
        box-shadow: 0 7px 18px rgba(0, 0, 0, .12);
    }

    .fa-login__benefit:nth-child(2) .fa-login__benefit-icon {
        background: #18b589;
    }

    .fa-login__benefit:nth-child(3) .fa-login__benefit-icon {
        background: #ffad24;
    }

    .fa-login__benefit strong {
        display: block;
        margin-bottom: 5px;
        color: #fff;
        font-size: 18px;
        font-weight: 800;
    }

    .fa-login__benefit p {
        margin: 0;
        color: rgba(255, 255, 255, .94);
        font-size: 14px;
        line-height: 1.6;
    }

    /* TABLET */

    @media (max-width: 1000px) {
        .fa-login {
            grid-template-columns: 48% 52%;
        }

        .fa-login__left {
            padding: 35px 28px;
            border-radius: 0 35px 35px 0;
        }

        .fa-login__brand {
            margin-bottom: 32px;
        }

        .fa-login__hero {
            padding: 75px 28px 35px;
            background-position: 60% center;
        }

        .fa-login__benefits {
            gap: 18px;
            margin-top: 30px;
        }
    }

    /* MOBILE */

    @media (max-width: 700px) {
        .fa-login {
            display: flex;
            flex-direction: column;
            overflow: visible;
        }

        .fa-login__left {
            order: 1;
            padding: 30px 23px 35px;
            border-radius: 0 0 26px 26px;
        }

        .fa-login__brand {
            margin-bottom: 32px;
            font-size: 28px;
        }

        .fa-login__brand img {
            width: 48px;
            height: 50px;
        }

        .fa-login__heading h1 {
            font-size: 36px;
        }

        .fa-login__hero {
            order: 2;
            min-height: 540px;
            padding: 43px 25px;
            background-position: 65% center;
        }

        .fa-login__hero h2 {
            font-size: 36px;
        }

        .fa-login__benefits {
            gap: 17px;
            margin-top: 30px;
        }

        .fa-login__benefit-icon {
            width: 47px;
            height: 47px;
            flex-basis: 47px;
            font-size: 18px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .fa-login__input,
        .fa-login__submit {
            transition: none;
        }
    }
</style>

<main class="fa-login">

    {{-- LEFT SIDE: EXISTING LARAVEL LOGIN FUNCTIONALITY --}}

    <section class="fa-login__left" aria-labelledby="fa-login-title">

        <a class="fa-login__brand"
           href="{{ route('home') }}"
           aria-label="YouthHub Partner home">

            <img src="{{ asset('icon.png') }}"
                 alt="YouthHub Partner logo">

            <span>YouthHub <strong>Partner</strong></span>
        </a>

        <div class="fa-login__heading">

            <span class="fa-login__badge">
                {{ strtoupper($portalName) }} Portal
            </span>

            <h1 id="fa-login-title">Welcome Back</h1>

            <p>{{ $portalDescription }}</p>

        </div>

        @if ($errors->any())
            <div class="fa-login__error" role="alert">
                <i class="fa-solid fa-circle-exclamation"
                   aria-hidden="true"></i>

                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST"
              action="{{ $loginAction }}"
              class="fa-login__form">

            @csrf

            <div class="fa-login__field">
                <label for="sakhi_id">Field Agent ID</label>

                <div class="fa-login__input-wrap">
                    <i class="fa-regular fa-user fa-field-icon"
                       aria-hidden="true"></i>

                    <input
                        class="fa-login__input"
                        id="sakhi_id"
                        name="sakhi_id"
                        type="text"
                        value="{{ old('sakhi_id') }}"
                        placeholder="Enter your Field Agent ID"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="fa-login__field">
                <label for="password">Password</label>

                <div class="fa-login__input-wrap">
                    <i class="fa-solid fa-lock fa-field-icon"
                       aria-hidden="true"></i>

                    <input
                        class="fa-login__input"
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        class="fa-login__password-toggle"
                        type="button"
                        id="fa-toggle-password"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        <i class="fa-regular fa-eye"
                           aria-hidden="true"></i>
                    </button>


                </div>
            </div>

            <button class="fa-login__submit" type="submit">
                <span>Sign in to {{ $portalName }}</span>
                <i class="fa-solid fa-arrow-right"
                   aria-hidden="true"></i>
            </button>

        </form>

        <div class="fa-login__support">
            <i class="fa-solid fa-shield-halved"
               aria-hidden="true"></i>

            <span>
                Contact your organisation administrator
                if you need account access.
            </span>
        </div>

    </section>

    {{-- RIGHT SIDE: FIELD AGENT LOGIN HERO --}}

    <aside class="fa-login__hero"
           aria-label="YouthHub Partner Field Agent Login">

        <div class="fa-login__hero-content">

            <div class="fa-login__accent"></div>

            <p class="fa-login__hero-eyebrow">
                YOUTHHUB PARTNER
            </p>

            <h2>Field Agent Login</h2>

            <p class="fa-login__hero-subtitle">
                Connect <span>|</span> Support <span>|</span> Create Impact
            </p>

            <div class="fa-login__benefits">

                <div class="fa-login__benefit">
                    <span class="fa-login__benefit-icon">
                        <i class="fa-solid fa-users"
                           aria-hidden="true"></i>
                    </span>

                    <div>
                        <strong>Reach</strong>
                        <p>More young people</p>
                    </div>
                </div>

                <div class="fa-login__benefit">
                    <span class="fa-login__benefit-icon">
                        <i class="fa-solid fa-graduation-cap"
                           aria-hidden="true"></i>
                    </span>

                    <div>
                        <strong>Enable</strong>
                        <p>Better opportunities</p>
                    </div>
                </div>

                <div class="fa-login__benefit">
                    <span class="fa-login__benefit-icon">
                        <i class="fa-solid fa-chart-line"
                           aria-hidden="true"></i>
                    </span>

                    <div>
                        <strong>Build</strong>
                        <p>A stronger tomorrow</p>
                    </div>
                </div>

            </div>
        </div>

    </aside>

</main>

<script>
    (function () {
        const toggle = document.getElementById('fa-toggle-password');
        const password = document.getElementById('password');

        if (!toggle || !password) {
            return;
        }

        toggle.addEventListener('click', function () {
            const showPassword = password.type === 'password';

            password.type = showPassword ? 'text' : 'password';

            toggle.setAttribute(
                'aria-pressed',
                showPassword ? 'true' : 'false'
            );

            toggle.setAttribute(
                'aria-label',
                showPassword ? 'Hide password' : 'Show password'
            );

            toggle.innerHTML = showPassword
                ? '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>'
                : '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
        });
    })();
</script>

@endsection
