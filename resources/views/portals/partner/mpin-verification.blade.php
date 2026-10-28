
@extends('layouts.app')

@section('content')
<main class="rbk-mpin-page">
    <div class="rbk-mpin-layout">

        <!-- Left-side security and branding panel -->
        <aside class="rbk-mpin-hero">
            <div class="rbk-brand">
                <div class="rbk-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" fill="none">
                        <path d="M24 4L41 11V23C41 33 34 40 24 44C14 40 7 33 7 23V11L24 4Z"
                              stroke="currentColor" stroke-width="2.5"/>
                        <path d="M16 24L21.5 29.5L32.5 18"
                              stroke="currentColor" stroke-width="3"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <strong>YouthHub Partner</strong>
                    <span>PARTNER PORTAL</span>
                </div>
            </div>

            <div class="rbk-hero-content">
                <div class="rbk-hero-badge">
                    <span></span> ADDITIONAL SECURITY
                </div>

                <h1>Your workspaces.<br>Protected by you.</h1>

                <p>
                    One more layer of security keeps your field workspace
                    protected. Verify your identity with your six-digit
                    MPIN to continue.
                </p>

                <div class="rbk-security-visual" aria-hidden="true">
                    <div class="rbk-orbit rbk-orbit-one"></div>
                    <div class="rbk-orbit rbk-orbit-two"></div>

                    <div class="rbk-shield">
                        <svg viewBox="0 0 80 80" fill="none">
                            <path d="M40 8L65 18V36C65 51 55 64 40 72C25 64 15 51 15 36V18L40 8Z"
                                  stroke="currentColor" stroke-width="3"/>
                            <rect x="29" y="35" width="22" height="19"
                                  rx="4" stroke="currentColor" stroke-width="3"/>
                            <path d="M34 35V29C34 21 46 21 46 29V35"
                                  stroke="currentColor" stroke-width="3"
                                  stroke-linecap="round"/>
                            <circle cx="40" cy="44" r="2.5" fill="currentColor"/>
                        </svg>
                    </div>

                    <div class="rbk-floating-chip">
                        <span class="rbk-chip-dot"></span>
                        <span>MPIN</span>
                        <span class="rbk-chip-dots">••••••</span>
                        <span class="rbk-chip-check">✓</span>
                    </div>
                </div>
            </div>

            <div class="rbk-hero-footer">
                <span class="rbk-footer-shield">✓</span>
                Secure access to your field workspace
            </div>
        </aside>

        <!-- MPIN verification card -->
        <section class="rbk-mpin-main">
            <div class="rbk-mpin-card">

                <div class="rbk-card-topline">
                    <span class="rbk-card-accent"></span>
                    <span class="rbk-secure-label">
                        <span class="rbk-secure-dot"></span>
                        SECURE ACCESS
                    </span>
                </div>

                <div class="rbk-mobile-brand">
                    <div class="rbk-brand-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" fill="none">
                            <path d="M24 4L41 11V23C41 33 34 40 24 44C14 40 7 33 7 23V11L24 4Z"
                                  stroke="currentColor" stroke-width="2.5"/>
                            <path d="M16 24L21.5 29.5L32.5 18"
                                  stroke="currentColor" stroke-width="3"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <strong>YouthHub Partner</strong>
                </div>

                <header class="rbk-card-heading">
                    <div class="rbk-lock-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="10" width="15" height="11"
                                  rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round"/>
                            <circle cx="12" cy="15" r="1.4" fill="currentColor"/>
                        </svg>
                    </div>

                    <h2>Enter MPIN</h2>
                    <p>
                        Verify your identity to continue to your dashboard.
                    </p>
                </header>

                @if ($errors->any())
                    <div class="rbk-error" role="alert" aria-live="polite">
                        <span class="rbk-error-icon">!</span>
                        <div>
                            <strong>Verification unsuccessful</strong>
                            <p>{{ $errors->first('mpin') }}</p>
                        </div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="rbk-status-message" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Logged-in Field Agent profile -->
                <div class="rbk-profile">
                    <div class="rbk-avatar" aria-hidden="true">
                        {{ collect(preg_split('/\s+/', trim($fieldAgent->name ?? 'Field Agent')))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_substr($part, 0, 1))
                            ->implode('') }}
                    </div>

                    <div class="rbk-profile-details">
                        <strong>{{ $partner->name }}</strong>
                        <span>Partner · Secure verification</span>
                    </div>

                    <div class="rbk-profile-status" title="Active session">
                        <span></span>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('partner.mpin.verify.submit') }}"
                    id="rbkMpinForm"
                >
                    @csrf

                    <div class="rbk-mpin-label-row">
                        <label for="rbkMpinDigit1">Enter your MPIN</label>
                        <span>6 DIGITS</span>
                    </div>

                    <div class="rbk-digit-boxes" id="rbkDigitBoxes">
                        @for ($i = 1; $i <= 6; $i++)
                            <input
                                type="password"
                                id="rbkMpinDigit{{ $i }}"
                                class="rbk-digit"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="1"
                                autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                                aria-label="MPIN digit {{ $i }}"
                                @if ($i === 1) autofocus @endif
                            >
                        @endfor
                    </div>

                    <!-- Complete six-digit MPIN submitted to Laravel -->
                    <input
                        type="hidden"
                        name="mpin"
                        id="rbkMpinValue"
                        value=""
                    >

                    <p class="rbk-mpin-hint" id="rbkMpinHint" aria-live="polite">
                        <span class="rbk-hint-shield">✓</span>
                        Your MPIN is securely verified. Never share it with anyone.
                    </p>

                    <button
                        type="submit"
                        class="rbk-continue-button"
                        id="rbkContinueButton"
                    >
                        <span class="rbk-button-text">Continue to Dashboard</span>
                        <span class="rbk-button-arrow" aria-hidden="true">→</span>
                        <span class="rbk-button-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="rbk-card-divider"></div>

                <form
                    method="POST"
                    action="{{ route('partner.logout') }}"
                    id="rbkLogoutForm"
                >
                    @csrf
                    <button type="submit" class="rbk-logout-button">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 17L15 12L10 7M15 12H3"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 3H19C20.1 3 21 3.9 21 5V19C21 20.1 20.1 21 19 21H12"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round"/>
                        </svg>
                        Log out of your account
                    </button>
                </form>

                <p class="rbk-card-footer">
                    <span>🔒</span> Protected Field Agent access
                </p>
            </div>

            <p class="rbk-copyright">
                YouthHub Partner Portal · Secure Authentication
            </p>
        </section>
    </div>
</main>

<style>
    /*
     * YouthHub Field Agent MPIN Verification
     * Namespaced styles prevent conflicts with existing portal components.
     */
    .rbk-mpin-page {
        --rbk-primary: #2f6dcc;
        --rbk-primary-dark: #1d56ad;
        --rbk-heading: #30364f;
        --rbk-text: #687086;
        --rbk-muted: #929aaa;
        --rbk-border: #e1e7ef;
        --rbk-surface: #ffffff;
        --rbk-success: #28b878;
        --rbk-danger: #d94848;

        min-height: 100vh;
        width: 100%;
        margin: 0;
        padding: 0;
        background: #f5f8fc;
        color: var(--rbk-heading);
        font-family: "Poppins", "Segoe UI", Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .rbk-mpin-page *,
    .rbk-mpin-page *::before,
    .rbk-mpin-page *::after {
        box-sizing: border-box;
    }

    .rbk-mpin-layout {
        display: grid;
        grid-template-columns: minmax(0, 46%) minmax(0, 54%);
        min-height: 100vh;
        overflow: hidden;
    }

    /* Left blue panel with floating security illustration. */
    .rbk-mpin-hero {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        padding: 46px clamp(28px, 5vw, 76px) 30px;
        overflow: hidden;
        color: #fff;
        background:
            radial-gradient(circle at 12% 15%, rgba(255,255,255,.13), transparent 29%),
            radial-gradient(circle at 90% 78%, rgba(99,180,255,.20), transparent 32%),
            linear-gradient(145deg, #4389e5 0%, #2f6dcc 50%, #194f9d 100%);
    }

    .rbk-mpin-hero::before {
        content: "";
        position: absolute;
        width: 440px;
        height: 440px;
        left: -270px;
        bottom: -230px;
        border: 65px solid rgba(255,255,255,.055);
        border-radius: 50%;
        pointer-events: none;
    }

    .rbk-mpin-hero::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        top: 17%;
        right: -170px;
        border: 1px solid rgba(255,255,255,.13);
        border-radius: 50%;
        pointer-events: none;
    }

    .rbk-brand {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .rbk-brand-icon {
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        padding: 10px;
        border: 1px solid rgba(255,255,255,.55);
        border-radius: 14px;
        background: rgba(255,255,255,.14);
        color: #fff;
    }

    .rbk-brand-icon svg {
        display: block;
        width: 100%;
        height: 100%;
    }

    .rbk-brand strong,
    .rbk-mobile-brand strong {
        display: block;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -.35px;
    }

    .rbk-brand div:last-child span {
        display: block;
        margin-top: 4px;
        color: rgba(255,255,255,.72);
        font-size: 9px;
        font-weight: 600;
        letter-spacing: 1.6px;
    }

    .rbk-hero-content {
        position: relative;
        z-index: 1;
        margin: 45px 0 35px;
    }

    .rbk-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 9px 12px;
        border: 1px solid rgba(255,255,255,.22);
        border-radius: 30px;
        background: rgba(255,255,255,.08);
        color: rgba(255,255,255,.9);
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1.1px;
    }

    .rbk-hero-badge > span,
    .rbk-secure-dot,
    .rbk-profile-status span,
    .rbk-chip-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #51e0a0;
        box-shadow: 0 0 0 4px rgba(81,224,160,.12);
    }

    .rbk-hero-content h1 {
        margin: 27px 0 18px;
        color: #fff;
        font-size: clamp(34px, 3.2vw, 51px);
        font-weight: 700;
        line-height: 1.18;
        letter-spacing: -1.9px;
    }

    .rbk-hero-content > p {
        max-width: 400px;
        margin: 0;
        color: rgba(255,255,255,.76);
        font-size: 13px;
        line-height: 1.95;
    }

    .rbk-security-visual {
        position: relative;
        display: grid;
        place-items: center;
        width: min(100%, 390px);
        height: 205px;
        margin: 32px auto 0;
    }

    .rbk-orbit {
        position: absolute;
        width: 180px;
        height: 180px;
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 50%;
    }

    .rbk-orbit-two {
        width: 240px;
        height: 240px;
        border-style: dashed;
        border-color: rgba(255,255,255,.11);
    }

    .rbk-shield {
        position: relative;
        z-index: 1;
        display: grid;
        place-items: center;
        width: 115px;
        height: 115px;
        border: 1px solid rgba(255,255,255,.35);
        border-radius: 30px;
        background: linear-gradient(145deg, rgba(255,255,255,.20), rgba(255,255,255,.06));
        box-shadow: 0 25px 60px rgba(12,44,105,.22);
        color: #fff;
        backdrop-filter: blur(12px);
    }

    .rbk-shield svg {
        width: 76px;
        height: 76px;
    }

    .rbk-floating-chip {
        position: absolute;
        z-index: 2;
        right: 0;
        bottom: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border: 1px solid rgba(255,255,255,.22);
        border-radius: 13px;
        background: rgba(255,255,255,.12);
        box-shadow: 0 18px 45px rgba(14,43,96,.16);
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        backdrop-filter: blur(15px);
    }

    .rbk-chip-dots {
        letter-spacing: 3px;
    }

    .rbk-chip-check {
        display: grid;
        place-items: center;
        width: 19px;
        height: 19px;
        border-radius: 50%;
        background: #51d69a;
        color: #104d38;
    }

    .rbk-hero-footer {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-top: 19px;
        border-top: 1px solid rgba(255,255,255,.16);
        color: rgba(255,255,255,.78);
        font-size: 10px;
    }

    .rbk-footer-shield {
        display: grid;
        place-items: center;
        width: 23px;
        height: 23px;
        border-radius: 50%;
        background: rgba(255,255,255,.15);
        color: #fff;
    }

    /* Right panel and verification card. */
    .rbk-mpin-main {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 0;
        padding: 42px clamp(20px, 4vw, 58px) 20px;
        background:
            radial-gradient(circle at 100% 0%, rgba(47,109,204,.035), transparent 30%),
            #f7f9fd;
    }

    .rbk-mpin-card {
        position: relative;
        width: 100%;
        max-width: 500px;
        padding: 43px clamp(23px, 3vw, 43px) 28px;
        border: 1px solid #e5eaf2;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 26px 70px rgba(39,49,78,.10);
    }

    .rbk-card-topline {
        position: absolute;
        top: 0;
        left: 36px;
        right: 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .rbk-card-accent {
        width: 62px;
        height: 4px;
        border-radius: 0 0 8px 8px;
        background: linear-gradient(90deg, #2f6dcc, #4da7ef);
    }

    .rbk-secure-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 15px;
        color: #239965;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .8px;
    }

    .rbk-secure-dot {
        width: 6px;
        height: 6px;
        box-shadow: none;
    }

    .rbk-card-heading {
        text-align: center;
    }

    .rbk-lock-icon {
        display: grid;
        place-items: center;
        width: 54px;
        height: 54px;
        margin: 4px auto 15px;
        border: 1px solid #dfebff;
        border-radius: 16px;
        background: #edf5ff;
        color: #2f6dcc;
    }

    .rbk-lock-icon svg {
        width: 27px;
        height: 27px;
    }

    .rbk-card-heading h2 {
        margin: 0 0 9px;
        color: #30364f;
        font-size: clamp(26px, 2.2vw, 31px);
        font-weight: 700;
        letter-spacing: -.9px;
    }

    .rbk-card-heading > p {
        max-width: 330px;
        margin: 0 auto 24px;
        color: #929aaa;
        font-size: 12px;
        line-height: 1.8;
    }

    .rbk-profile {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 29px;
        padding: 15px;
        border: 1px solid #edf0f6;
        border-radius: 14px;
        background: #fbfcff;
    }

    .rbk-avatar {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border: 3px solid #fff;
        border-radius: 50%;
        background: linear-gradient(140deg, #4389e5, #194f9d);
        box-shadow: 0 5px 15px rgba(47,109,204,.18);
        color: #fff;
        font-size: 15px;
        font-weight: 700;
    }

    .rbk-profile-details {
        flex: 1;
        min-width: 0;
    }

    .rbk-profile-details strong {
        display: block;
        overflow: hidden;
        color: #30364f;
        font-size: 12px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rbk-profile-details span {
        display: block;
        margin-top: 5px;
        color: #929aaa;
        font-size: 9px;
    }

    .rbk-profile-status {
        display: grid;
        place-items: center;
        width: 25px;
        height: 25px;
        flex: 0 0 25px;
        border: 1px solid #d7f4e6;
        border-radius: 50%;
        background: #edfbf4;
    }

    .rbk-profile-status span {
        width: 7px;
        height: 7px;
        box-shadow: none;
    }

    .rbk-error,
    .rbk-status-message {
        display: flex;
        gap: 11px;
        margin-bottom: 20px;
        padding: 13px;
        border: 1px solid #f5d6d6;
        border-radius: 11px;
        background: #fff6f6;
        color: #b93636;
        font-size: 11px;
        line-height: 1.6;
    }

    .rbk-error-icon {
        display: grid;
        place-items: center;
        width: 20px;
        height: 20px;
        flex: 0 0 20px;
        border-radius: 50%;
        background: #fbe2e2;
        font-weight: 700;
    }

    .rbk-error strong {
        display: block;
        margin-bottom: 2px;
    }

    .rbk-error p {
        margin: 0;
    }

    .rbk-status-message {
        border-color: #d7f4e6;
        background: #f0fcf6;
        color: #168052;
    }

    .rbk-mpin-label-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    .rbk-mpin-label-row label {
        color: #4e566b;
        font-size: 11px;
        font-weight: 600;
    }

    .rbk-mpin-label-row > span {
        padding: 6px 9px;
        border-radius: 20px;
        background: #edf5ff;
        color: #2f6dcc;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .7px;
    }

    .rbk-digit-boxes {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: clamp(5px, .8vw, 9px);
    }

    .rbk-digit {
        display: block;
        width: 100%;
        min-width: 0;
        height: 59px;
        margin: 0;
        padding: 0;
        border: 1px solid #e1e7ef;
        border-radius: 12px;
        outline: none;
        background: #f8faff;
        color: #30364f;
        font-family: inherit;
        font-size: 23px;
        font-weight: 700;
        text-align: center;
        caret-color: #2f6dcc;
        box-shadow: none;
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .2s ease;
        -webkit-text-security: disc;
    }

    .rbk-digit:hover {
        border-color: #c4cfde;
    }

    .rbk-digit:focus {
        border-color: #2f6dcc;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(47,109,204,.09);
        transform: translateY(-2px);
    }

    .rbk-digit.filled {
        border-color: rgba(47,109,204,.45);
        background: #f0f6ff;
    }

    .rbk-digit.invalid {
        border-color: #ed5050;
        background: #fff7f7;
    }

    .rbk-mpin-hint {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin: 13px 0 0;
        color: #929aaa;
        font-size: 9px;
        line-height: 1.7;
    }

    .rbk-hint-shield {
        display: grid;
        place-items: center;
        width: 17px;
        height: 17px;
        flex: 0 0 17px;
        border-radius: 50%;
        background: #e9f9f1;
        color: #239965;
        font-size: 10px;
        font-weight: 700;
    }

    .rbk-continue-button {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        min-height: 55px;
        margin-top: 25px;
        padding: 12px 20px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #2f6dcc, #1e58ad);
        color: #fff;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 10px 25px rgba(47,109,204,.23);
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .rbk-continue-button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(47,109,204,.3);
    }

    .rbk-continue-button:disabled {
        cursor: wait;
        opacity: .8;
    }

    .rbk-button-arrow {
        position: absolute;
        right: 19px;
        font-size: 20px;
        font-weight: 400;
    }

    .rbk-button-spinner {
        display: none;
        width: 19px;
        height: 19px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: rbk-spin .7s linear infinite;
    }

    .rbk-continue-button.is-loading .rbk-button-text,
    .rbk-continue-button.is-loading .rbk-button-arrow {
        display: none;
    }

    .rbk-continue-button.is-loading .rbk-button-spinner {
        display: block;
    }

    @keyframes rbk-spin {
        to { transform: rotate(360deg); }
    }

    .rbk-card-divider {
        height: 1px;
        margin-top: 24px;
        background: #edf0f5;
    }

    .rbk-logout-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        margin-top: 17px;
        padding: 8px;
        border: none;
        background: transparent;
        color: #d65151;
        font-family: inherit;
        font-size: 10px;
        font-weight: 600;
        cursor: pointer;
    }

    .rbk-logout-button:hover {
        color: #b93636;
    }

    .rbk-logout-button svg {
        width: 17px;
        height: 17px;
    }

    .rbk-card-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        margin: 17px 0 0;
        color: #a1a9b7;
        font-size: 9px;
    }

    .rbk-copyright {
        margin: 19px 0 0;
        color: #9aa4b4;
        font-size: 9px;
        text-align: center;
    }

    .rbk-mobile-brand {
        display: none;
    }

    /* Tablet layout: compact blue header and centered verification card. */
    @media (max-width: 1000px) {
        .rbk-mpin-layout {
            grid-template-columns: minmax(0, 40%) minmax(0, 60%);
        }

        .rbk-mpin-hero {
            padding: 35px 27px 25px;
        }

        .rbk-hero-content h1 {
            font-size: 35px;
        }

        .rbk-security-visual {
            transform: scale(.85);
            transform-origin: center;
        }

        .rbk-mpin-main {
            padding-right: 24px;
            padding-left: 24px;
        }
    }

    /* Mobile layout: remove the side panel and show a compact brand header. */
    @media (max-width: 760px) {
        .rbk-mpin-layout {
            display: block;
            min-height: 100vh;
            overflow: visible;
            background: linear-gradient(180deg, #2f6dcc 0, #2f6dcc 155px, #f5f8fc 155px, #f5f8fc 100%);
        }

        .rbk-mpin-hero {
            display: none;
        }

        .rbk-mpin-main {
            min-height: 100vh;
            justify-content: flex-start;
            padding: 23px 16px 25px;
            background: transparent;
        }

        .rbk-mpin-card {
            max-width: 500px;
            margin-top: 61px;
            padding: 39px 25px 25px;
            border-radius: 19px;
            box-shadow: 0 18px 50px rgba(39,49,78,.11);
        }

        .rbk-mobile-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 0 auto 29px;
        }

        .rbk-mobile-brand .rbk-brand-icon {
            width: 37px;
            height: 37px;
            flex-basis: 37px;
            padding: 7px;
            border: none;
            border-radius: 10px;
            background: #edf5ff;
            color: #2f6dcc;
        }

        .rbk-mobile-brand strong {
            color: #30364f;
            font-size: 14px;
        }

        .rbk-card-heading h2 {
            font-size: 27px;
        }

        .rbk-digit-boxes {
            gap: 7px;
        }

        .rbk-digit {
            height: 55px;
            border-radius: 10px;
            font-size: 21px;
        }

        .rbk-copyright {
            color: #7f8a9c;
        }
    }

    @media (max-width: 380px) {
        .rbk-mpin-main {
            padding-right: 11px;
            padding-left: 11px;
        }

        .rbk-mpin-card {
            padding-right: 16px;
            padding-left: 16px;
        }

        .rbk-digit-boxes {
            gap: 5px;
        }

        .rbk-digit {
            height: 49px;
            font-size: 18px;
        }

        .rbk-profile {
            gap: 9px;
            padding: 12px;
        }

        .rbk-profile-details span {
            font-size: 8px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .rbk-mpin-page *,
        .rbk-mpin-page *::before,
        .rbk-mpin-page *::after {
            animation-duration: .01ms !important;
            transition-duration: .01ms !important;
        }
    }
</style>

<script>
    /*
     * MPIN digit-entry controller.
     * Collects six numeric digits, supports navigation and paste,
     * and submits the complete value through Laravel's hidden MPIN field.
     */
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('rbkMpinForm');
        const boxesContainer = document.getElementById('rbkDigitBoxes');
        const boxes = Array.from(document.querySelectorAll('.rbk-digit'));
        const mpinInput = document.getElementById('rbkMpinValue');
        const button = document.getElementById('rbkContinueButton');
        const hint = document.getElementById('rbkMpinHint');

        if (!form || !boxesContainer || boxes.length !== 6 || !mpinInput || !button) {
            return;
        }

        /*
         * Synchronize the six visible digit boxes with the submitted MPIN.
         */
        function updateMpin() {
            mpinInput.value = boxes.map(function (box) {
                return box.value;
            }).join('');

            boxes.forEach(function (box) {
                box.classList.toggle('filled', box.value !== '');
                box.classList.remove('invalid');
            });
        }

        /*
         * Populate the digit boxes from keyboard input or a pasted MPIN.
         */
        function fillDigits(value, startIndex) {
            const digits = value.replace(/\D/g, '').slice(0, 6);
            const start = Math.max(0, Math.min(startIndex, 5));

            for (let i = start; i < 6; i++) {
                boxes[i].value = '';
            }

            for (let i = 0; i < digits.length && start + i < 6; i++) {
                boxes[start + i].value = digits[i];
            }

            updateMpin();

            const nextIndex = Math.min(start + digits.length, 5);
            boxes[nextIndex].focus();

            if (digits.length > 0 && start + digits.length >= 6) {
                boxes[5].focus();
            }
        }

        boxes.forEach(function (box, index) {
            box.addEventListener('input', function () {
                const digits = this.value.replace(/\D/g, '');

                if (digits.length > 1) {
                    fillDigits(digits, index);
                    return;
                }

                this.value = digits.slice(0, 1);
                updateMpin();

                if (this.value && index < boxes.length - 1) {
                    boxes[index + 1].focus();
                }
            });

            box.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace') {
                    if (this.value === '' && index > 0) {
                        boxes[index - 1].focus();
                        boxes[index - 1].value = '';
                        updateMpin();
                    } else {
                        this.value = '';
                        updateMpin();
                    }

                    event.preventDefault();
                    return;
                }

                if (event.key === 'ArrowLeft' && index > 0) {
                    boxes[index - 1].focus();
                    event.preventDefault();
                }

                if (event.key === 'ArrowRight' && index < boxes.length - 1) {
                    boxes[index + 1].focus();
                    event.preventDefault();
                }
            });

            box.addEventListener('paste', function (event) {
                event.preventDefault();

                const pasted = event.clipboardData
                    ? event.clipboardData.getData('text')
                    : '';

                fillDigits(pasted, 0);
            });

            box.addEventListener('focus', function () {
                this.select();
            });
        });

        /*
         * Focus the first digit automatically when the verification page opens.
         */
        const firstBox = boxes[0];

        if (firstBox && !boxes.some(function (box) { return box.value !== ''; })) {
            firstBox.focus();
        }

        /*
         * Prevent incomplete submissions and show a loading indicator
         * while a valid MPIN is submitted to the server.
         */
        form.addEventListener('submit', function (event) {
            updateMpin();

            if (!/^\d{6}$/.test(mpinInput.value)) {
                event.preventDefault();

                boxes.forEach(function (box) {
                    if (!box.value) {
                        box.classList.add('invalid');
                    }
                });

                if (hint) {
                    hint.textContent = 'Please enter all six digits of your MPIN.';
                    hint.style.color = '#d94848';
                }

                const firstEmpty = boxes.find(function (box) {
                    return box.value === '';
                });

                if (firstEmpty) {
                    firstEmpty.focus();
                }

                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');
        });
    });
</script>
@endsection
