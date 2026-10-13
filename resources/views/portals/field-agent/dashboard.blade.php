
@extends('layouts.app')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/field-agent-dashboard.css') }}?v=1.0.1">
@endpush

@section('content')
    <main class="field-agent-app" id="field-agent-app">

        {{-- Mobile drawer overlay --}}
        <div
            class="field-agent-drawer-overlay"
            id="field-agent-drawer-overlay">
        </div>

        {{-- Sidebar / mobile drawer --}}
        <aside
            class="field-agent-drawer"
            id="field-agent-drawer"
            aria-label="Main navigation"
            aria-hidden="false">

            <a class="portal-brand"
               href="{{ route('field-agent.dashboard') }}">
                <span class="portal-brand__mark">Y</span>
                <span>YouthHub <strong>Field</strong></span>
            </a>

            <p class="field-agent-drawer__label">WORKSPACE</p>

            <nav class="field-agent-nav" aria-label="Workspace navigation">
                <a href="{{ route('field-agent.dashboard') }}"
                   data-home-link
                   data-section="overview"
                   class="active"
                   aria-current="page">
                    <span class="field-agent-nav__icon">⌂</span>
                    <span>Overview</span>
                </a>

                <button type="button" data-section="learners">
                    <span class="field-agent-nav__icon">♙</span>
                    <span>My Learners</span>
                </button>

                <button type="button" data-section="activities">
                    <span class="field-agent-nav__icon">▤</span>
                    <span>Events</span>
                </button>

                <button type="button" data-section="reports">
                    <span class="field-agent-nav__icon">▥</span>
                    <span>Reports</span>
                </button>
            </nav>

            <p class="field-agent-drawer__label">ACCOUNT</p>

            <nav class="field-agent-nav" aria-label="Account navigation">
                <button type="button" data-section="profile">
                    <span class="field-agent-nav__icon">◎</span>
                    <span>My Profile</span>
                </button>
            </nav>

            <div class="field-agent-drawer__bottom">
                <form method="POST"
                      action="{{ route('field-agent.logout') }}">
                    @csrf
                    <button class="field-agent-signout" type="submit">
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Top header --}}
        <header class="field-agent-header">
            <button
                type="button"
                class="field-agent-menu-toggle"
                id="field-agent-menu-toggle"
                aria-label="Open navigation menu"
                aria-controls="field-agent-drawer"
                aria-expanded="false">
                ☰
            </button>

            <a class="portal-brand"
               href="{{ route('field-agent.dashboard') }}"
               aria-label="Field Agent home">
                <span class="portal-brand__mark">Y</span>
                <span>YouthHub <strong>Field</strong></span>
            </a>

            <span class="field-agent-header__status">
                <i></i>
                {{ ucfirst($fieldAgent->status) }}
            </span>

            <form method="POST"
                  action="{{ route('field-agent.logout') }}">
                @csrf
                <button class="field-agent-signout" type="submit">
                    Sign out
                </button>
            </form>
        </header>

        {{-- React dashboard root --}}
        <section class="field-agent-main">
            <div
                id="field-agent-react-root"
                class="field-agent-react-root"
                data-react-app="field-agent-dashboard"
                data-agent="{{ json_encode([
                    'name' => $fieldAgent->name,
                    'status' => $fieldAgent->status,
                    'email' => $fieldAgent->email,
                    'contactNumber' => $fieldAgent->contact_number,
                    'cscId' => $fieldAgent->csc_id,
                    'sakhiId' => $fieldAgent->sakhi_id,
                    'state' => $fieldAgent->state,
                    'district' => $fieldAgent->district,
                    'partnerName' => $fieldAgent->partner?->name,
                    'partnerCode' => $fieldAgent->partner?->partner_code,
                    'centerName' => $fieldAgent->partnerCenter?->name,
                    'centerCode' => $fieldAgent->partnerCenter?->partner_code,
                    'placementName' => $fieldAgent->placementPartnerUser?->partner_placement_name,
                    'placementCode' => $fieldAgent->placementPartnerUser?->pp_code,
                ]) }}">
            </div>

            <footer class="field-agent-footer">
                YOUTHHUB PARTNER
                <span>Field operations</span>
            </footer>
        </section>

        {{-- Mobile bottom navigation --}}
        <nav class="field-agent-bottom-nav"
             aria-label="Mobile navigation">

            <a href="{{ route('field-agent.dashboard') }}"
               data-home-link
               data-section="overview"
               class="active"
               aria-current="page">
                <span>⌂</span>
                <span>Home</span>
            </a>

            <button type="button" data-section="learners">
                <span>♙</span>
                <span>Learners</span>
            </button>

            <button type="button" data-section="activities">
                <span>▤</span>
                <span>Events</span>
            </button>

            <button type="button" data-section="profile">
                <span>◎</span>
                <span>Profile</span>
            </button>
        </nav>

    </main>

    <script>
        (() => {
            const app = document.getElementById('field-agent-app');

            if (!app || app.dataset.navigationInitialized === 'true') {
                return;
            }

            app.dataset.navigationInitialized = 'true';

            const toggle = document.getElementById('field-agent-menu-toggle');
            const drawer = document.getElementById('field-agent-drawer');
            const overlay = document.getElementById('field-agent-drawer-overlay');

            if (!toggle || !drawer || !overlay) {
                return;
            }

            const isMobile = () =>
                window.matchMedia('(max-width: 1023px)').matches;

            function setDrawer(open) {
                const shouldOpen = Boolean(open) && isMobile();

                // Move focus before hiding the drawer from accessibility tools.
                if (
                    !shouldOpen &&
                    drawer.contains(document.activeElement)
                ) {
                    toggle.focus({ preventScroll: true });
                }

                app.classList.toggle('drawer-open', shouldOpen);

                toggle.setAttribute('aria-expanded', String(shouldOpen));
                toggle.setAttribute(
                    'aria-label',
                    shouldOpen
                        ? 'Close navigation menu'
                        : 'Open navigation menu'
                );

                // Desktop sidebar stays available; mobile drawer is hidden when closed.
                drawer.setAttribute(
                    'aria-hidden',
                    String(isMobile() && !shouldOpen)
                );

                document.body.style.overflow =
                    shouldOpen ? 'hidden' : '';
            }

            function updateActiveNavigation(section) {
                app.querySelectorAll('[data-section]').forEach(link => {
                    const isActive = link.dataset.section === section;

                    link.classList.toggle('active', isActive);

                    if (isActive) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            }

            toggle.addEventListener('click', () => {
                setDrawer(!app.classList.contains('drawer-open'));
            });

            overlay.addEventListener('click', () => {
                setDrawer(false);
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    setDrawer(false);
                }
            });

            window.addEventListener('resize', () => {
                setDrawer(false);
            });

            // One navigation handler for desktop sidebar and mobile navigation.
            app.querySelectorAll('[data-section]').forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();

                    const section = link.dataset.section;

                    if (!section) {
                        return;
                    }

                    setDrawer(false);
                    updateActiveNavigation(section);

                    window.dispatchEvent(
                        new CustomEvent('field-agent:navigate', {
                            detail: { section }
                        })
                    );
                });
            });

            // Set correct initial drawer accessibility state.
            setDrawer(false);
            updateActiveNavigation('overview');

        })();
    </script>
@endsection
