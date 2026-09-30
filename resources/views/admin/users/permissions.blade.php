@extends('layouts.app')

@section('content')
    <main class="admin-users-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.users.index') }}">&larr; Admin users</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-permissions-content">
            <p class="admin-auth-eyebrow">ROLE ACCESS</p>
            <h1>Sub Admin permissions</h1>
            <p class="admin-users-intro">Choose which modules every Sub Admin account can access. Changes apply to all users with this role.</p>

            @if (session('status'))
                <div class="admin-users-success" role="status">{{ session('status') }}</div>
            @endif
            @error('permissions')
                <div class="admin-auth-error" role="alert">{{ $message }}</div>
            @enderror

            <form method="POST" action="{{ route('admin.users.permissions.update') }}" class="admin-permissions-form">
                @csrf
                @method('PUT')

                <section class="admin-permissions-base">
                    <span class="admin-permissions-lock" aria-hidden="true">✓</span>
                    <span><strong>Admin dashboard</strong><small>Required so Sub Admins can sign in and reach their workspace.</small></span>
                    <span class="admin-permissions-required">Always on</span>
                </section>

                <div class="admin-permissions-grid">
                    @foreach ($groups as $group => $permissions)
                        <fieldset class="admin-permission-group">
                            <legend>{{ $group }}</legend>
                            @foreach ($permissions as $permission => $label)
                                <label class="admin-permission-option" for="permission-{{ $loop->parent->index }}-{{ $loop->index }}">
                                    <input
                                        id="permission-{{ $loop->parent->index }}-{{ $loop->index }}"
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission }}"
                                        @checked(in_array($permission, old('permissions', $assignedPermissions), true))
                                    >
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>

                <div class="admin-permissions-footer">
                    <a href="{{ route('admin.users.index') }}">Cancel</a>
                    <button type="submit">Save Sub Admin access</button>
                </div>
            </form>
        </section>
    </main>
@endsection
