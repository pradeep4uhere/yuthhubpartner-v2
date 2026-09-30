@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.partners.index') }}">&larr; Partners</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-form-content">
            <p class="admin-auth-eyebrow">PARTNER DIRECTORY</p>
            <h1>{{ $pageTitle }}</h1>
            <p class="admin-users-intro">Enter the organisation details and choose its current status.</p>

            <section class="admin-partners-form-panel">
                <form method="POST" action="{{ $formAction }}" class="admin-partners-form">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif

                    <label for="partner_code">Partner code</label>
                    <input id="partner_code" name="partner_code" value="{{ old('partner_code', $partner->partner_code) }}" maxlength="255" required>
                    @error('partner_code')<small>{{ $message }}</small>@enderror

                    <label for="name">Partner name</label>
                    <input id="name" name="name" value="{{ old('name', $partner->name) }}" maxlength="255" required>
                    @error('name')<small>{{ $message }}</small>@enderror

                    <label for="contact_number">Contact number</label>
                    <input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number', $partner->contact_number) }}" maxlength="30">
                    @error('contact_number')<small>{{ $message }}</small>@enderror

                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $partner->email) }}" maxlength="255">
                    @error('email')<small>{{ $message }}</small>@enderror

                    <label for="password">{{ $partner->exists ? 'New portal password (optional)' : 'Portal password' }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $partner->exists)>
                    @error('password')<small>{{ $message }}</small>@enderror

                    <label for="password_confirmation">Confirm portal password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $partner->exists)>

                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected(old('status', $partner->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $partner->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<small>{{ $message }}</small>@enderror

                    <div class="admin-partners-form-actions">
                        <a href="{{ route('admin.partners.index') }}">Cancel</a>
                        <button type="submit">{{ $partner->exists ? 'Save changes' : 'Create partner' }}</button>
                    </div>
                </form>
            </section>
        </section>
    </main>
@endsection
