@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.field-agents.index') }}">&larr; Field agents</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-form-content">
            <p class="admin-auth-eyebrow">FIELD OPERATIONS</p>
            <h1>{{ $pageTitle }}</h1>
            <p class="admin-users-intro">Assign the agent to a partner, center and placement organisation.</p>

            <section class="admin-partners-form-panel">
                <form method="POST" action="{{ $formAction }}" class="admin-partners-form">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif

                    <label for="name">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $fieldAgent->name) }}" required>
                    @error('name')<small>{{ $message }}</small>@enderror

                    <label for="email">Login email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $fieldAgent->email) }}" required>
                    @error('email')<small>{{ $message }}</small>@enderror

                    <label for="contact_number">Contact number</label>
                    <input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number', $fieldAgent->contact_number) }}">
                    @error('contact_number')<small>{{ $message }}</small>@enderror

                    <label for="csc_id">CSC ID</label>
                    <input id="csc_id" name="csc_id" value="{{ old('csc_id', $fieldAgent->csc_id) }}">
                    @error('csc_id')<small>{{ $message }}</small>@enderror

                    <label for="sakhi_id">Sakhi ID</label>
                    <input id="sakhi_id" name="sakhi_id" value="{{ old('sakhi_id', $fieldAgent->sakhi_id) }}">
                    @error('sakhi_id')<small>{{ $message }}</small>@enderror

                    <label for="partner_id">Partner</label>
                    <select id="partner_id" name="partner_id" required>
                        <option value="">Select partner</option>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}" @selected((string) old('partner_id', $fieldAgent->partner_id) === (string) $partner->id)>{{ $partner->name }} ({{ $partner->partner_code }})</option>
                        @endforeach
                    </select>
                    @error('partner_id')<small>{{ $message }}</small>@enderror

                    <label for="partner_center_id">Partner center</label>
                    <select id="partner_center_id" name="partner_center_id" required>
                        <option value="">Select center for selected partner</option>
                        @foreach ($partnerCenters as $center)
                            <option value="{{ $center->id }}" data-partner-id="{{ $center->partner_id }}" @selected((string) old('partner_center_id', $fieldAgent->partner_center_id) === (string) $center->id)>{{ $center->partner->name }} / {{ $center->name }}</option>
                        @endforeach
                    </select>
                    @error('partner_center_id')<small>{{ $message }}</small>@enderror

                    <label for="placement_partner_user_id">Placement partner user</label>
                    <select id="placement_partner_user_id" name="placement_partner_user_id" required>
                        <option value="">Select placement partner</option>
                        @foreach ($placementUsers as $placementUser)
                            <option value="{{ $placementUser->id }}" @selected((string) old('placement_partner_user_id', $fieldAgent->placement_partner_user_id) === (string) $placementUser->id)>{{ $placementUser->partner_placement_name }} ({{ $placementUser->pp_code }})</option>
                        @endforeach
                    </select>
                    @error('placement_partner_user_id')<small>{{ $message }}</small>@enderror

                    <label for="state">State</label>
                    <input id="state" name="state" value="{{ old('state', $fieldAgent->state) }}" required>
                    @error('state')<small>{{ $message }}</small>@enderror

                    <label for="district">District</label>
                    <input id="district" name="district" value="{{ old('district', $fieldAgent->district) }}" required>
                    @error('district')<small>{{ $message }}</small>@enderror

                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected(old('status', $fieldAgent->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $fieldAgent->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<small>{{ $message }}</small>@enderror

                    <label for="password">{{ $fieldAgent->exists ? 'New password (optional)' : 'Login password' }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $fieldAgent->exists)>
                    @error('password')<small>{{ $message }}</small>@enderror

                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $fieldAgent->exists)>

                    <div class="admin-partners-form-actions">
                        <a href="{{ route('admin.field-agents.index') }}">Cancel</a>
                        <button type="submit">{{ $fieldAgent->exists ? 'Save changes' : 'Create field agent' }}</button>
                    </div>
                </form>
            </section>
        </section>
    </main>
    <script>
        const partnerSelect = document.querySelector('#partner_id');
        const centerSelect = document.querySelector('#partner_center_id');

        function filterPartnerCenters() {
            const partnerId = partnerSelect.value;

            for (const option of centerSelect.options) {
                if (!option.value) continue;
                option.hidden = option.dataset.partnerId !== partnerId;
                if (option.hidden && option.selected) centerSelect.value = '';
            }
        }

        partnerSelect.addEventListener('change', filterPartnerCenters);
        filterPartnerCenters();
    </script>
@endsection
