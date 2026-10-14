@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.event-masters.index') }}">&larr; Event masters</a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-form-content">
            <p class="admin-auth-eyebrow">EVENT MANAGEMENT</p>

            <h1>{{ $pageTitle ?? 'Create Event Master' }}</h1>

            <p class="admin-users-intro">
                Create and manage event master details, category and required documents.
            </p>

            <section class="admin-partners-form-panel">
                <form
                    method="POST"
                    action="{{ route('admin.event-masters.store') }}"
                    class="admin-partners-form"
                >
                    @csrf

                    {{-- Event Type --}}
                    <label for="event_master_type_id">Event Type</label>

                    <select id="event_master_type_id" name="event_master_type_id" required>
                        <option value="">Select event type</option>

                        @foreach ($eventMasterTypes as $eventMasterType)
                            <option
                                value="{{ $eventMasterType->id }}"
                                @selected(
                                    (string) old('event_master_type_id') ===
                                    (string) $eventMasterType->id
                                )
                            >
                                {{ $eventMasterType->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('event_master_type_id')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Event Category --}}
                    <label for="event_category_id">Event Category</label>

                    <select id="event_category_id" name="event_category_id" required>
                        <option value="">Select category</option>

                        @foreach ($eventCategories as $category)
                            <option
                                value="{{ $category->id }}"
                                data-type-id="{{ $category->event_master_type_id }}"
                                @selected(
                                    (string) old('event_category_id') ===
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
                    <label for="event_name">Event Name</label>

                    <input
                        id="event_name"
                        name="event_name"
                        value="{{ old('event_name') }}"
                        required
                    >

                    @error('event_name')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Description --}}
                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                    >{{ old('description') }}</textarea>

                    @error('description')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Eligibility --}}
                    <label for="eligibility">Eligibility</label>

                    <input
                        id="eligibility"
                        name="eligibility"
                        value="{{ old('eligibility') }}"
                    >

                    @error('eligibility')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Fees --}}
                    <label for="fees_per_transactions">Fees Per Transaction</label>

                    <input
                        id="fees_per_transactions"
                        name="fees_per_transactions"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('fees_per_transactions') }}"
                    >

                    @error('fees_per_transactions')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Document 1 --}}
                    <label for="document_name_1">Document Name 1</label>

                    <input
                        id="document_name_1"
                        name="document_name_1"
                        value="{{ old('document_name_1') }}"
                    >

                    @error('document_name_1')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Document 2 --}}
                    <label for="document_name_2">Document Name 2</label>

                    <input
                        id="document_name_2"
                        name="document_name_2"
                        value="{{ old('document_name_2') }}"
                    >

                    @error('document_name_2')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Document 3 --}}
                    <label for="document_name_3">Document Name 3</label>

                    <input
                        id="document_name_3"
                        name="document_name_3"
                        value="{{ old('document_name_3') }}"
                    >

                    @error('document_name_3')
                        <small>{{ $message }}</small>
                    @enderror


                    {{-- Status --}}
                    <label for="status">Status</label>

                    <select id="status" name="status" required>
                        <option
                            value="1"
                            @selected(old('status', '1') == '1')
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


                    {{-- Actions --}}
                    <div class="admin-partners-form-actions">
                        <a href="{{ route('admin.event-masters.index') }}">
                            Cancel
                        </a>

                        <button type="submit">
                            Create event master
                        </button>
                    </div>

                </form>
            </section>
        </section>
    </main>


    {{-- Event Type -> Category dependent dropdown --}}
    <script>
        const eventTypeSelect = document.querySelector('#event_master_type_id');
        const categorySelect = document.querySelector('#event_category_id');

        function filterEventCategories() {
            const typeId = eventTypeSelect.value;

            for (const option of categorySelect.options) {
                if (!option.value) {
                    continue;
                }

                option.hidden = option.dataset.typeId !== typeId;

                if (option.hidden && option.selected) {
                    categorySelect.value = '';
                }
            }
        }

        eventTypeSelect.addEventListener('change', filterEventCategories);

        filterEventCategories();
    </script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('event_master_type_id');
    const categorySelect = document.getElementById('event_category_id');

    if (!typeSelect || !categorySelect) return;

    const categoryUrlTemplate = @json(
        route('admin.event-categories.by-type', ['typeId' => '__TYPE_ID__'])
    );

    async function loadCategories(typeId, selectedCategoryId = '') {
        categorySelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = typeId
            ? 'Loading categories...'
            : 'Select event type first';

        categorySelect.appendChild(placeholder);
        categorySelect.disabled = true;

        if (!typeId) return;

        try {
            const url = categoryUrlTemplate.replace(
                '__TYPE_ID__',
                encodeURIComponent(typeId)
            );

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Unable to load categories. HTTP ' + response.status);
            }

            const result = await response.json();

            categorySelect.innerHTML = '';

            const firstOption = document.createElement('option');
            firstOption.value = '';
            firstOption.textContent = 'Select event category';
            categorySelect.appendChild(firstOption);

            (result.data || []).forEach(function (category) {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                option.selected =
                    String(category.id) === String(selectedCategoryId);

                categorySelect.appendChild(option);
            });

            if (!result.data || result.data.length === 0) {
                firstOption.textContent = 'No categories available';
            }

            categorySelect.disabled = false;
        } catch (error) {
            console.error('Category loading error:', error);
            categorySelect.innerHTML = '<option value="">Failed to load categories</option>';
            categorySelect.disabled = true;
        }
    }

    typeSelect.addEventListener('change', function () {
        loadCategories(this.value);
    });

    // Restore categories after validation errors
    const oldTypeId = @json(old('event_master_type_id'));
    const oldCategoryId = @json(old('event_category_id'));

    if (oldTypeId) {
        loadCategories(oldTypeId, oldCategoryId);
    }
});
</script>
@endsection
