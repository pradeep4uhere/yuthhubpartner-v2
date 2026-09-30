@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.partner-centers.index') }}">&larr; Partner centers</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-form-content">
            <p class="admin-auth-eyebrow">PARTNER NETWORK</p>
            <h1>{{ $pageTitle }}</h1>
            <p class="admin-users-intro">Select the parent partner and enter the center details.</p>

            <section class="admin-partners-form-panel">
                <form method="POST" action="{{ $formAction }}" class="admin-partners-form">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif

                    <label for="partner_id">Parent partner</label>
                    <select id="partner_id" name="partner_id" required>
                        <option value="">Select a partner</option>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}" @selected((string) old('partner_id', $partnerCenter->partner_id) === (string) $partner->id)>{{ $partner->name }} ({{ $partner->partner_code }})</option>
                        @endforeach
                    </select>
                    @error('partner_id')<small>{{ $message }}</small>@enderror

                    <label for="partner_code">Center code</label>
                    <input id="partner_code" name="partner_code" value="{{ old('partner_code', $partnerCenter->partner_code) }}" maxlength="255" required>
                    @error('partner_code')<small>{{ $message }}</small>@enderror

                    <label for="name">Center name</label>
                    <input id="name" name="name" value="{{ old('name', $partnerCenter->name) }}" maxlength="255" required>
                    @error('name')<small>{{ $message }}</small>@enderror

                    <label for="contact_number">Contact number</label>
                    <input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number', $partnerCenter->contact_number) }}" maxlength="30">
                    @error('contact_number')<small>{{ $message }}</small>@enderror

                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $partnerCenter->email) }}" maxlength="255">
                    @error('email')<small>{{ $message }}</small>@enderror

                    <label for="password">{{ $partnerCenter->exists ? 'New portal password (optional)' : 'Portal password' }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $partnerCenter->exists)>
                    @error('password')<small>{{ $message }}</small>@enderror

                    <label for="password_confirmation">Confirm portal password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $partnerCenter->exists)>

                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected(old('status', $partnerCenter->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $partnerCenter->status) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<small>{{ $message }}</small>@enderror

                    <div class="admin-partners-form-actions">
                        <a href="{{ route('admin.partner-centers.index') }}">Cancel</a>
                        <button type="submit">{{ $partnerCenter->exists ? 'Save changes' : 'Create center' }}</button>
                    </div>
                </form>
            </section>
        </section>
    </main>
@endsection
