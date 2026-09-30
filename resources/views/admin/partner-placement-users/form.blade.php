@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.partner-placement-users.index') }}">&larr; Placement users</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-form-content">
            <p class="admin-auth-eyebrow">PLACEMENT NETWORK</p>
            <h1>{{ $pageTitle }}</h1>
            <p class="admin-users-intro">Enter the placement organisation details and portal credentials.</p>

            <section class="admin-partners-form-panel">
                <form method="POST" action="{{ $formAction }}" class="admin-partners-form">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif

                    <label for="name">User name</label>
                    <input id="name" name="name" value="{{ old('name', $placementUser->name) }}" maxlength="255" required>
                    @error('name')<small>{{ $message }}</small>@enderror

                    <label for="email">Login email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $placementUser->email) }}" maxlength="255" required>
                    @error('email')<small>{{ $message }}</small>@enderror

                    <label for="partner_placement_name">Placement organisation</label>
                    <input id="partner_placement_name" name="partner_placement_name" value="{{ old('partner_placement_name', $placementUser->partner_placement_name) }}" maxlength="255" required>
                    @error('partner_placement_name')<small>{{ $message }}</small>@enderror

                    <label for="contact_number">Contact number</label>
                    <input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number', $placementUser->contact_number) }}" maxlength="30">
                    @error('contact_number')<small>{{ $message }}</small>@enderror

                    <label for="pp_code">Placement code</label>
                    <input id="pp_code" name="pp_code" value="{{ old('pp_code', $placementUser->pp_code) }}" maxlength="255" required>
                    @error('pp_code')<small>{{ $message }}</small>@enderror

                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected(old('status', $placementUser->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $placementUser->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<small>{{ $message }}</small>@enderror

                    <label for="password">{{ $placementUser->exists ? 'New password (optional)' : 'Portal password' }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $placementUser->exists)>
                    @error('password')<small>{{ $message }}</small>@enderror

                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $placementUser->exists)>

                    <div class="admin-partners-form-actions">
                        <a href="{{ route('admin.partner-placement-users.index') }}">Cancel</a>
                        <button type="submit">{{ $placementUser->exists ? 'Save changes' : 'Create placement user' }}</button>
                    </div>
                </form>
            </section>
        </section>
    </main>
@endsection
