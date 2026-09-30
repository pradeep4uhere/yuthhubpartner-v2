@extends('layouts.app')

@section('content') <main class="admin-partners-page">


    <header class="admin-users-header">

        <a href="{{ route('admin.event-masters.index') }}">
            &larr; Event masters
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

        <h1>Edit Event Master</h1>

        <p class="admin-users-intro">
            Update event master details, category, eligibility, fees and required documents.
        </p>


        <section class="admin-partners-form-panel">

            <form
                method="POST"
                action="{{ route('admin.event-masters.update', $eventMaster) }}"
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
                                    $eventMaster->event_master_type_id
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


               {{-- Event Category --}}
                <label for="event_category_id">
                    Event Category
                </label>

                <select
                    id="event_category_id"
                    name="event_category_id"
                    required
                >
                    <option value="">
                        Select event category
                    </option>

                    @foreach ($eventCategories as $category)

                        <option
                            value="{{ $category->id }}"
                            @selected(
                                (string) old(
                                    'event_category_id',
                                    $eventMaster->event_category_id
                                ) ===
                                (string) $category->id
                            )
                        >
                            {{ $category->name }}
                        </option>

                    @endforeach
                </select>

                @error('event_category_id')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Event Name --}}
                <label for="event_name">
                    Event Name
                </label>

                <input
                    id="event_name"
                    name="event_name"
                    value="{{ old('event_name', $eventMaster->event_name) }}"
                    placeholder="Enter event name"
                    required
                >

                @error('event_name')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Description --}}
                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Enter event description"
                >{{ old('description', $eventMaster->description) }}</textarea>

                @error('description')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Eligibility --}}
                <label for="eligibility">
                    Eligibility
                </label>

                <input
                    id="eligibility"
                    name="eligibility"
                    value="{{ old('eligibility', $eventMaster->eligibility) }}"
                    placeholder="Enter eligibility criteria"
                >

                @error('eligibility')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Fee --}}
                <label for="fees_per_transactions">
                    Fee / Transaction
                </label>

                <input
                    id="fees_per_transactions"
                    name="fees_per_transactions"
                    type="number"
                    step="0.01"
                    min="0"
                    value="{{ old('fees_per_transactions', $eventMaster->fees_per_transactions) }}"
                    placeholder="Enter fee per transaction"
                >

                @error('fees_per_transactions')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Document 1 --}}
                <label for="document_name_1">
                    Document 1
                </label>

                <input
                    id="document_name_1"
                    name="document_name_1"
                    value="{{ old('document_name_1', $eventMaster->document_name_1) }}"
                    placeholder="Enter document name"
                >

                @error('document_name_1')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Document 2 --}}
                <label for="document_name_2">
                    Document 2
                </label>

                <input
                    id="document_name_2"
                    name="document_name_2"
                    value="{{ old('document_name_2', $eventMaster->document_name_2) }}"
                    placeholder="Enter document name"
                >

                @error('document_name_2')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Document 3 --}}
                <label for="document_name_3">
                    Document 3
                </label>

                <input
                    id="document_name_3"
                    name="document_name_3"
                    value="{{ old('document_name_3', $eventMaster->document_name_3) }}"
                    placeholder="Enter document name"
                >

                @error('document_name_3')
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
                                $eventMaster->status ? '1' : '0'
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
                                $eventMaster->status ? '1' : '0'
                            ) === '0'
                        )
                    >
                        Inactive
                    </option>

                </select>

                @error('status')
                    <small>{{ $message }}</small>
                @enderror


                {{-- Form Actions --}}
                <div class="admin-partners-form-actions">

                    <a href="{{ route('admin.event-masters.index') }}">
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
<script>
document.addEventListener('DOMContentLoaded', function () {

    const eventTypeSelect = document.getElementById('event_master_type_id');
    const categorySelect = document.getElementById('event_category_id');

    if (!eventTypeSelect || !categorySelect) {
        return;
    }

    eventTypeSelect.addEventListener('change', function () {

        const eventTypeId = this.value;

        categorySelect.innerHTML = `
            <option value="">
                Loading categories...
            </option>
        `;

        categorySelect.disabled = true;

        if (!eventTypeId) {

            categorySelect.innerHTML = `
                <option value="">
                    Select event category
                </option>
            `;

            categorySelect.disabled = false;

            return;
        }

        fetch(
            `{{ url('/admin/event-masters/categories') }}/${eventTypeId}`,
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        )
        .then(response => {

            if (!response.ok) {
                throw new Error('Unable to load categories.');
            }

            return response.json();
        })
        .then(categories => {

            categorySelect.innerHTML = `
                <option value="">
                    Select event category
                </option>
            `;

            categories.forEach(category => {

                const option = document.createElement('option');

                option.value = category.id;
                option.textContent = category.name;

                categorySelect.appendChild(option);
            });

            categorySelect.disabled = false;
        })
        .catch(error => {

            console.error(error);

            categorySelect.innerHTML = `
                <option value="">
                    Unable to load categories
                </option>
            `;

            categorySelect.disabled = false;
        });

    });

});
</script>
@endsection
