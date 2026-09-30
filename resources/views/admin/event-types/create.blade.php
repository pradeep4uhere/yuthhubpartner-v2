@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">

        <header class="admin-users-header">

            <a href="{{ route('admin.event-types.index') }}">
                &larr; Event types
            </a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>

        </header>


        <section class="admin-partners-form-content">

            <p class="admin-auth-eyebrow">
                EVENT MANAGEMENT
            </p>

            <h1>Create Event Type</h1>

            <p class="admin-users-intro">
                Add a new event type for event categories and event masters.
            </p>


            <section class="admin-partners-form-panel">

                <form
                    method="POST"
                    action="{{ route('admin.event-types.store') }}"
                    class="admin-partners-form"
                >

                    @csrf


                    {{-- Event Type Name --}}
                    <label for="name">
                        Event Type
                    </label>

                    <input
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Enter event type"
                        required
                    >

                    @error('name')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Status --}}
                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >

                        <option
                            value="1"
                            @selected(old('status', '1') === '1')
                        >
                            Active
                        </option>

                        <option
                            value="0"
                            @selected(old('status') === '0')
                        >
                            Inactive
                        </option>

                    </select>

                    @error('status')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Form Actions --}}
                    <div class="admin-partners-form-actions">

                        <a href="{{ route('admin.event-types.index') }}">
                            Cancel
                        </a>

                        <button type="submit">
                            Create event type
                        </button>

                    </div>

                </form>

            </section>

        </section>

    </main>
@endsection