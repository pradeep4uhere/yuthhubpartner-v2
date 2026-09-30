@extends('layouts.app')

@section('content')
    <main class="admin-users-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.dashboard') }}">&larr; Dashboard</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-users-content">
            <p class="admin-auth-eyebrow">ACCESS CONTROL</p>
            <h1>Admin users</h1>
            <p class="admin-users-intro">Create administrator accounts and assign their workspace access.</p>
            <a class="admin-permission-link" href="{{ route('admin.users.permissions.edit') }}">Manage Sub Admin module access</a>

            @if (session('status'))
                <div class="admin-users-success" role="status">{{ session('status') }}</div>
            @endif
            @error('admin')
                <div class="admin-auth-error" role="alert">{{ $message }}</div>
            @enderror

            <div class="admin-users-grid">
                <section class="admin-users-form-panel">
                    <h2>Invite an administrator</h2>
                    <form method="POST" action="{{ route('admin.users.store') }}" class="admin-users-form">
                        @csrf
                        <label for="name">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                        @error('name')<small>{{ $message }}</small>@enderror

                        <label for="admin-email">Email address</label>
                        <input id="admin-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                        @error('email')<small>{{ $message }}</small>@enderror

                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                        @error('role')<small>{{ $message }}</small>@enderror

                        <label for="new-password">Temporary password</label>
                        <input id="new-password" name="password" type="password" autocomplete="new-password" required>
                        @error('password')<small>{{ $message }}</small>@enderror

                        <label for="password-confirmation">Confirm password</label>
                        <input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

                        <button type="submit">Create admin account</button>
                    </form>
                </section>

                <section class="admin-users-list-panel">
                    <div class="admin-users-list-heading">
                        <h2>Current administrators</h2>
                        <span>{{ $admins->count() }}</span>
                    </div>
                    <div class="admin-users-list">
                        @forelse ($admins as $admin)
                            <article class="admin-user-row">
                                <span class="admin-user-avatar">{{ str($admin->name)->substr(0, 1)->upper() }}</span>
                                <span class="admin-user-details"><strong>{{ $admin->name }}</strong><small>{{ $admin->email }}</small></span>
                                <span class="admin-user-role">{{ $admin->getRoleNames()->join(', ') ?: 'No role' }}</span>
                                <span class="admin-user-state {{ $admin->is_active ? 'is-active' : 'is-inactive' }}">{{ $admin->is_active ? 'Active' : 'Inactive' }}</span>
                                @if (! auth('admin')->user()->is($admin))
                                    <form method="POST" action="{{ route('admin.users.status', $admin) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-user-toggle" type="submit">{{ $admin->is_active ? 'Disable' : 'Activate' }}</button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            <p class="admin-users-empty">No admin accounts have been created.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </main>
@endsection
