
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
            aria-label="{{ __('field_agent.main_navigation') }}"
            aria-hidden="false">

            <a class="portal-brand"
               href="{{ route('field-agent.dashboard') }}">
                <img src="{{ asset('icon.png') }}" alt="YouthHub Partner logo" height="50px" width="50px">
                <span>{{ __('field_agent.brand') }}</span>
            </a>

            <p class="field-agent-drawer__label">
                {{ __('field_agent.workspace') }}
            </p>

            <nav class="field-agent-nav"
                 aria-label="{{ __('field_agent.workspace_navigation') }}">

                <a href="{{ route('field-agent.dashboard') }}"
                   data-home-link
                   data-section="overview"
                   class="active"
                   aria-current="page">
                    <span class="field-agent-nav__icon">⌂</span>
                    <span>{{ __('field_agent.overview') }}</span>
                </a>

                <button type="button" data-section="learners">
                    <span class="field-agent-nav__icon">♙</span>
                    <span>{{ __('field_agent.learners') }}</span>
                </button>

                <button type="button" data-section="activities">
                    <span class="field-agent-nav__icon">▤</span>
                    <span>{{ __('field_agent.events') }}</span>
                </button>

                {{-- Partner Announcements Navigation --}}
                <button type="button" data-section="announcements">

                    <span class="field-agent-nav__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone" aria-hidden="true"><path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"></path><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"></path><path d="M8 6v8"></path></svg>
                    </span>

                    <span>Announcements</span>

                    {{-- Updated dynamically through the React dashboard --}}
                    <span
                        class="fa-ann-nav-count"
                        data-announcement-count
                        hidden>
                        0
                    </span>

                </button>


            </nav>

            <p class="field-agent-drawer__label">
                {{ __('field_agent.account') }}
            </p>

            <nav class="field-agent-nav"
                 aria-label="{{ __('field_agent.account_navigation') }}">

                <button type="button" data-section="profile">
                    <span class="field-agent-nav__icon">◎</span>
                    <span>{{ __('field_agent.profile') }}</span>
                </button>

                {{-- Support & Help navigation --}}
                <button type="button" data-section="support">
                    <span class="field-agent-nav__icon">ⓘ</span>
                    <span>{{ __('field_agent.support_help') }}</span>
                </button>
            </nav>

            <div class="field-agent-drawer__bottom">
                <form method="POST"
                      action="{{ route('field-agent.logout') }}">
                    @csrf

                    <button class="field-agent-signout" type="submit">
                        <i class="bi bi-door-open" aria-hidden="true"></i>
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
                aria-label="{{ __('field_agent.open_menu') }}"
                aria-controls="field-agent-drawer"
                aria-expanded="false">
                ☰
            </button>

            <a class="portal-brand"
               href="{{ route('field-agent.dashboard') }}"
               aria-label="{{ __('field_agent.field_agent_home') }}">
                <img src="{{ asset('icon.png') }}" alt="YouthHub Partner logo" height="30px" width="30px">
                <span>{{ __('field_agent.brand') }}</span>
            </a>

            <span class="field-agent-header__status">
                <i></i>
                {{ __('field_agent.statuses.' . strtolower($fieldAgent->status)) }}
            </span>

            {{-- Language selector --}}
            <form method="POST"
                  action="{{ route('language.update') }}"
                  class="field-agent-language-form">
                @csrf

                <div class="language-icon" aria-hidden="true">
                    <i class="bi bi-translate"></i>
                </div>

                <select
                    id="field-agent-language"
                    name="locale"
                    onchange="this.form.submit()"
                    aria-label="{{ __('field_agent.language') }}">

                    <option value="en"
                        {{ app()->getLocale() === 'en' ? 'selected' : '' }}>
                        English
                    </option>

                    <option value="hi"
                        {{ app()->getLocale() === 'hi' ? 'selected' : '' }}>
                        हिन्दी
                    </option>
                </select>
            </form>

            <form method="POST"
                  action="{{ route('field-agent.logout') }}">
                @csrf

                <button class="field-agent-signout" type="submit">
                    {{ __('field_agent.sign_out') }}
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

            {{-- React translations --}}
            <script type="application/json"
                    id="field-agent-translations">
                @json(__('field_agent.react'))
            </script>

            <footer class="field-agent-footer">
                {{ __('field_agent.footer_brand') }}
                <span>{{ __('field_agent.field_operations') }}</span>
            </footer>
        </section>

        {{-- Mobile bottom navigation --}}
        <nav class="field-agent-bottom-nav"
             aria-label="{{ __('field_agent.mobile_navigation') }}">

            <a href="{{ route('field-agent.dashboard') }}"
               data-home-link
               data-section="overview"
               class="active"
               aria-current="page">
                <span>⌂</span>
                <span>{{ __('field_agent.home') }}</span>
            </a>

            <button type="button" data-section="learners">
                <span>♙</span>
                <span>{{ __('field_agent.learners_short') }}</span>
            </button>

            <button type="button" data-section="activities">
                <span>▤</span>
                <span>{{ __('field_agent.events') }}</span>
            </button>


            <button type="button" data-section="profile">
                <span>◎</span>
                <span>{{ __('field_agent.profile_short') }}</span>
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
                        ? @json(__('field_agent.close_menu'))
                        : @json(__('field_agent.open_menu'))
                );

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

            app.querySelectorAll('[data-section]').forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();

                    const section = link.dataset.section;

                    if (!section) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Announcement Unread Count Synchronization
                    |--------------------------------------------------------------------------
                    | Receives the unread count from the React Field Agent Dashboard
                    | and updates both desktop and mobile navigation badges.
                    */

                    // Update every announcement badge in the Field Agent navigation.
                    function updateAnnouncementBadges(count) {

                        const unread = Math.max(0, Number(count) || 0);

                        app.querySelectorAll('[data-announcement-count]').forEach(badge => {

                            badge.textContent = unread > 99 ? '99+' : String(unread);

                            badge.hidden = unread === 0;

                        });
                    }

                    // Listen for unread-count updates dispatched by React.
                    window.addEventListener('field-agent:announcement-count', event => {

                        updateAnnouncementBadges(event.detail?.count ?? 0);

                    });

                    setDrawer(false);
                    updateActiveNavigation(section);

                    window.dispatchEvent(
                        new CustomEvent('field-agent:navigate', {
                            detail: { section }
                        })
                    );
                });
            });

            setDrawer(false);
            updateActiveNavigation('overview');

        })();
    </script>
@endsection
