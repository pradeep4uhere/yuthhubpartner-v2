@extends('layouts.app')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* Placement Partner login – isolated from existing Partner module */
.pp-login, .pp-login * { box-sizing:border-box; }
.pp-login { --blue:#0868e8; --navy:#08295b; position:relative; isolation:isolate; min-height:100vh; overflow:hidden; font-family:Inter, sans-serif; color:var(--navy); background:#0a376a url("{{ asset('images/placement-partner-login-background.png') }}") center center / cover no-repeat; }
.pp-login::before { content:""; position:absolute; z-index:-1; inset:0; background:linear-gradient(90deg,rgba(4,35,84,.18),transparent 70%); }
.pp-login__white { position:absolute; z-index:0; inset:0 auto 0 0; width:52%; background:linear-gradient(115deg,#fff 0%,#f8fbff 72%,rgba(244,249,255,.97) 100%); clip-path:polygon(0 0,100% 0,100% 75%,94% 84%,82% 91%,65% 100%,0 100%); }
.pp-login__white::after {content:""; position:absolute; inset:0; background:radial-gradient(ellipse at 0 100%,rgba(26,113,245,.11),transparent 60%);}
.pp-login__form-side {position:relative; z-index:2; width:45%; min-height:100vh; display:flex; flex-direction:column; justify-content:center; padding:38px clamp(32px,6.8vw,145px) 55px clamp(32px,8.2vw,150px);}
.pp-login__brand {display:flex; align-items:center; gap:12px; margin-bottom:clamp(36px,6vh,70px); color:var(--navy); text-decoration:none;}
.pp-login__brand img {width:66px; height:66px; object-fit:contain;}
.pp-login__brand-name {font-size:clamp(26px,2.2vw,36px); font-weight:800; letter-spacing:-1.4px; line-height:1.05;}
.pp-login__brand-tag {display:inline-block; margin-top:5px; padding:4px 10px; border-radius:6px; background:var(--blue); color:#fff; font-size:13px; font-weight:700; letter-spacing:0;}
.pp-login__eyebrow {color:#0870ed; font-size:13px; font-weight:800; letter-spacing:4px; margin:0 0 12px;}
.pp-login h1 {font-size:clamp(30px,2.65vw,43px); letter-spacing:-1.5px; line-height:1.17; margin:0 0 15px; white-space:nowrap; font-weight:800;}
.pp-login h1 span {color:var(--blue);}
.pp-login__intro {font-size:14px; line-height:1.7; color:#5a7296; margin:0 0 25px; max-width:440px;}
.pp-login__form {padding:18px; border-radius:20px; background:rgba(255,255,255,.86); box-shadow:0 15px 50px rgba(25,79,146,.06); max-width:500px; width:100%;}
.pp-login__field {position:relative; margin-bottom:14px;}
.pp-login__field > i {position:absolute; left:17px; top:50%; transform:translateY(-50%); color:#2363b8; font-size:17px; pointer-events:none;}
.pp-login__input {width:100%; height:52px; border:1px solid #cbd9ef; background:#fff; border-radius:12px; padding:0 48px; font:500 14px Inter,sans-serif; color:#153767; outline:none;}
.pp-login__input:focus {border-color:var(--blue); box-shadow:0 0 0 4px rgba(8,104,232,.1);}
.pp-login__input::placeholder {color:#7791b5;}
.pp-login__eye {position:absolute; right:10px; top:50%; transform:translateY(-50%); width:34px; height:34px; border:0; background:transparent; color:#376398; cursor:pointer;}
.pp-login__submit {display:flex; justify-content:center; align-items:center; gap:14px; width:100%; height:54px; border:0; border-radius:11px; background:linear-gradient(95deg,#0876ed,#0758d5); color:#fff; font:700 16px Inter,sans-serif; cursor:pointer; margin-top:8px; box-shadow:0 8px 20px rgba(5,90,210,.18);}
.pp-login__submit:hover {filter:brightness(1.06);}
.pp-login__error {display:flex; gap:10px; padding:12px; background:#fff0f0; color:#a72525; border:1px solid #ffd0d0; border-radius:10px; font-size:13px; margin-bottom:14px;}
.pp-login__foot {display:flex; align-items:center; justify-content:center; gap:14px; max-width:500px; margin-top:25px; font-size:13px;}
.pp-login__foot a {color:var(--blue); font-weight:600; text-decoration:none;}
.pp-login__foot-line {flex:1; height:1px; background:#d8e4f3;}
.pp-login__secure {font-size:11px; color:#7890ac; max-width:500px; margin:25px 0 0; line-height:1.5; text-align:center;}
.pp-login__hero-heading {position:absolute; z-index:2; right:clamp(32px,5vw,100px); top:clamp(40px,6vh,90px); text-align:left; color:#fff; text-shadow:0 2px 15px rgba(0,20,60,.25);}
.pp-login__hero-heading h2 {font-size:clamp(25px,2.2vw,39px); line-height:1.18; letter-spacing:-1px; margin:0; font-weight:800;}
.pp-login__hero-heading h2 span {color:#61aaff;}
.pp-login__hero-heading::after {content:""; display:block; margin-top:18px; width:65px; height:3px; background:#4da3ff; border-radius:3px;}
.pp-login__features {position:absolute; z-index:2; right:0; bottom:0; width:60%; min-height:158px; padding:28px 38px 22px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); align-items:center; background:linear-gradient(100deg,rgba(5,53,119,.92),rgba(2,25,75,.98)); border-radius:70px 0 0 0; box-shadow:0 -10px 35px rgba(2,31,75,.15); color:#fff;}
.pp-login__feature {min-width:0; padding:0 14px; text-align:center; border-right:1px solid rgba(255,255,255,.23); font-size:12px; font-weight:600; line-height:1.5;}
.pp-login__feature:last-child {border:0;}
.pp-login__feature i {display:block; margin:0 0 12px; color:#2997ff; font-size:29px;}
@media(max-width:1150px) { .pp-login__white {width:58%;} .pp-login__form-side {width:51%; padding-left:45px; padding-right:45px;} .pp-login h1 {white-space:normal;} .pp-login__features {width:55%; padding:24px 10px;} .pp-login__feature {padding:0 7px; font-size:11px;} }
@media(max-width:800px) { .pp-login {background-position:65% center;} .pp-login__white {width:100%; clip-path:none; background:rgba(255,255,255,.94);} .pp-login__form-side {width:100%; min-height:auto; padding:35px 22px 40px; max-width:550px; margin:auto;} .pp-login__brand {margin-bottom:32px;} .pp-login__hero-heading {position:relative; right:auto; top:auto; padding:30px 25px; background:#0b3a79; text-align:center;} .pp-login__hero-heading::after {margin:16px auto 0;} .pp-login__features {position:relative; width:100%; border-radius:0; grid-template-columns:repeat(2,1fr); gap:24px 0;} .pp-login__feature:nth-child(2) {border:0;} .pp-login__secure {margin-bottom:0;} }
@media(max-width:420px) { .pp-login__brand-name {font-size:27px;} .pp-login h1 {font-size:29px;} .pp-login__form {padding:12px;} .pp-login__features {padding:26px 12px;} }
@media(prefers-reduced-motion:reduce) {.pp-login * {scroll-behavior:auto; transition:none!important;}}
</style>
<main class="pp-login">
    <div class="pp-login__white" aria-hidden="true"></div>
    <section class="pp-login__form-side" aria-labelledby="pp-login-title">
        <a class="pp-login__brand" href="{{ route('home') }}" aria-label="YouthHub home">
            <img src="{{ asset('icon.png') }}" alt="YouthHub logo">
            <span><span class="pp-login__brand-name">YouthHub <span>Partner</span><span>
        </a>
        <p class="pp-login__eyebrow">WELCOME BACK</p>
        <h1 id="pp-login-title">Placement Partner <span>Login</span></h1>
        <p class="pp-login__intro">Access your placement partner dashboard and manage your associated partners efficiently.</p>
        @if ($errors->any())
            <div class="pp-login__error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
        @endif
        <form method="POST" action="{{ $loginAction }}" class="pp-login__form">
            @csrf
            <div class="pp-login__field">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <input class="pp-login__input" id="pp-email" name="email" type="text" value="{{ old('email', old('partner_id')) }}" placeholder="Email Address" aria-label="Email or mobile number" autocomplete="username" required autofocus>
            </div>
            <div class="pp-login__field">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input class="pp-login__input" id="pp-password" name="password" type="password" placeholder="Password" aria-label="Password" autocomplete="current-password" required>
                <button class="pp-login__eye" type="button" id="pp-toggle-password" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
            </div>
            <button class="pp-login__submit" type="submit">Sign In <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </form>
        <div class="pp-login__foot"><span class="pp-login__foot-line"></span><a href="{{ route('home') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Home</a><span class="pp-login__foot-line"></span></div>
        <p class="pp-login__secure"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure access. Contact your organisation administrator if you need account assistance.</p>
    </section>
    <aside class="pp-login__hero-heading" aria-label="YouthHub mission"><h2>Empowering Youth<br>Creating <span>Opportunities</span></h2></aside>
    <div class="pp-login__features" aria-label="Placement partner portal features">
        <div class="pp-login__feature"><i class="fa-solid fa-users" aria-hidden="true"></i>Manage<br>Associated Partners</div>
        <div class="pp-login__feature"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>Track Performance<br>&amp; Progress</div>
        <div class="pp-login__feature"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Secure &amp;<br>Reliable Access</div>
        <div class="pp-login__feature"><i class="fa-solid fa-bullseye" aria-hidden="true"></i>Build a Stronger<br>Workforce</div>
    </div>
</main>
<script>
(function () {
    const toggle = document.getElementById('pp-toggle-password');
    const password = document.getElementById('pp-password');
    if (!toggle || !password) return;
    toggle.addEventListener('click', function () {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        toggle.innerHTML = visible ? '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>' : '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
    });
})();
</script>
@endsection
