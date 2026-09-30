@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">

        <header class="admin-users-header">

            <a href="{{ route('admin.event-categories.index') }}">
                &larr; Event categories
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

            <h1>Edit Event Category</h1>

            <p class="admin-users-intro">
                Update event category and event type assignment.
            </p>


            <section class="admin-partners-form-panel">

                <form
                    method="POST"
                    action="{{ route('admin.event-categories.update', $eventCategory) }}"
                    class="admin-partners-form"
                >

                    @csrf
                    @method('PUT')


                    {{-- Event Type --}}
                    <label for="event_master_type_id">
                        Event Type
                    </label>

                    <select
                        id="event_master_type_id"
                        name="event_master_type_id"
                        required
                    >
                        <option value="">
                            Select event type
                        </option>

                        @foreach ($eventMasterTypes as $type)
                            <option
                                value="{{ $type->id }}"
                                @selected(
                                    (string) old(
                                        'event_master_type_id',
                                        $eventCategory->event_master_type_id
                                    ) ===
                                    (string) $type->id
                                )
                            >
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('event_master_type_id')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Category Name --}}
                    <label for="name">
                        Category Name
                    </label>

                    <input
                        id="name"
                        name="name"
                        value="{{ old('name', $eventCategory->name) }}"
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
                            @selected(
                                old(
                                    'status',
                                    $eventCategory->status ? '1' : '0'
                                ) === '1'
                            )
                        >
                            Active
                        </option>

                        <option
                            value="0"
                            @selected(
                                old(
                                    'status',
                                    $eventCategory->status ? '1' : '0'
                                ) === '0'
                            )
                        >
                            Inactive
                        </option>
                    </select>

                    @error('status')
                        <small>{{ $message }}</small>
                    @enderror


                    <div class="admin-partners-form-actions">

                        <a href="{{ route('admin.event-categories.index') }}">
                            Cancel
                        </a>

                        <button type="submit">
                            Save changes
                        </button>

                    </div>

                </form>

            </section>

        </section>

    </main>
@endsection