@extends('layouts.app')

@section('content') <main class="admin-partners-page">
    <header class="admin-users-header">
        <a href="{{ route('admin.learners.index') }}">&larr; Learners</a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </header>

    <section class="admin-partners-form-content">

        <p class="admin-auth-eyebrow">LEARNER MANAGEMENT</p>

        <h1>{{ $pageTitle }}</h1>

        <p class="admin-users-intro">
            Add or update learner information and program details.
        </p>

        <section class="admin-partners-form-panel">

            <form method="POST"
                  action="{{ $formAction }}"
                  class="admin-partners-form">

                @csrf

                @if ($formMethod !== 'POST')
                    @method($formMethod)
                @endif


                {{-- =====================================================
                    BASIC INFORMATION
                ====================================================== --}}

                <label for="normalized_mobile">Normalized Mobile</label>

                <input
                    id="normalized_mobile"
                    name="normalized_mobile"
                    type="text"
                    value="{{ old('normalized_mobile', $learner->normalized_mobile) }}"
                    required
                >

                @error('normalized_mobile')
                    <small>{{ $message }}</small>
                @enderror


                <label for="phone_number">Phone Number</label>

                <input
                    id="phone_number"
                    name="phone_number"
                    type="tel"
                    value="{{ old('phone_number', $learner->phone_number) }}"
                >

                @error('phone_number')
                    <small>{{ $message }}</small>
                @enderror


                <label for="first_name">First Name</label>

                <input
                    id="first_name"
                    name="first_name"
                    type="text"
                    value="{{ old('first_name', $learner->first_name) }}"
                >

                @error('first_name')
                    <small>{{ $message }}</small>
                @enderror


                <label for="last_name">Last Name</label>

                <input
                    id="last_name"
                    name="last_name"
                    type="text"
                    value="{{ old('last_name', $learner->last_name) }}"
                >

                @error('last_name')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    PERSONAL INFORMATION
                ====================================================== --}}

                <label for="gender">Gender</label>

                <select id="gender" name="gender">
                    <option value="">Select gender</option>

                    <option value="Male"
                        @selected(old('gender', $learner->gender) === 'Male')>
                        Male
                    </option>

                    <option value="Female"
                        @selected(old('gender', $learner->gender) === 'Female')>
                        Female
                    </option>

                    <option value="Other"
                        @selected(old('gender', $learner->gender) === 'Other')>
                        Other
                    </option>
                </select>

                @error('gender')
                    <small>{{ $message }}</small>
                @enderror


                <label for="date_of_birth">Date of Birth</label>

                <input
                    id="date_of_birth"
                    name="date_of_birth"
                    type="date"
                    value="{{ old('date_of_birth', $learner->date_of_birth ? \Carbon\Carbon::parse($learner->date_of_birth)->format('Y-m-d') : '') }}"
                >

                @error('date_of_birth')
                    <small>{{ $message }}</small>
                @enderror


                <label for="email">Email</label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $learner->email) }}"
                >

                @error('email')
                    <small>{{ $message }}</small>
                @enderror


                <label for="marital_status">Marital Status</label>

                <select id="marital_status" name="marital_status">

                    <option value="">Select marital status</option>

                    <option value="Single"
                        @selected(old('marital_status', $learner->marital_status) === 'Single')>
                        Single
                    </option>

                    <option value="Married"
                        @selected(old('marital_status', $learner->marital_status) === 'Married')>
                        Married
                    </option>

                    <option value="Other"
                        @selected(old('marital_status', $learner->marital_status) === 'Other')>
                        Other
                    </option>

                </select>

                @error('marital_status')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    EDUCATION
                ====================================================== --}}

                <label for="education_level">Education Level</label>

                <input
                    id="education_level"
                    name="education_level"
                    type="text"
                    value="{{ old('education_level', $learner->education_level) }}"
                >

                @error('education_level')
                    <small>{{ $message }}</small>
                @enderror


                <label for="monthly_family_income_range">
                    Monthly Family Income Range
                </label>

                <input
                    id="monthly_family_income_range"
                    name="monthly_family_income_range"
                    type="text"
                    value="{{ old('monthly_family_income_range', $learner->monthly_family_income_range) }}"
                >

                @error('monthly_family_income_range')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    LOCATION
                ====================================================== --}}

                <label for="city">City</label>

                <input
                    id="city"
                    name="city"
                    type="text"
                    value="{{ old('city', $learner->city) }}"
                >

                @error('city')
                    <small>{{ $message }}</small>
                @enderror


                <label for="state">State</label>

                <input
                    id="state"
                    name="state"
                    type="text"
                    value="{{ old('state', $learner->state) }}"
                >

                @error('state')
                    <small>{{ $message }}</small>
                @enderror


                <label for="pin_code">Pin Code</label>

                <input
                    id="pin_code"
                    name="pin_code"
                    type="text"
                    value="{{ old('pin_code', $learner->pin_code) }}"
                >

                @error('pin_code')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    PROGRAM / INSTITUTE
                ====================================================== --}}

                <label for="program_code">Program Code</label>

                <input
                    id="program_code"
                    name="program_code"
                    type="text"
                    value="{{ old('program_code', $learner->program_code) }}"
                >

                @error('program_code')
                    <small>{{ $message }}</small>
                @enderror


                <label for="institute_state">Institute State</label>

                <input
                    id="institute_state"
                    name="institute_state"
                    type="text"
                    value="{{ old('institute_state', $learner->institute_state) }}"
                >

                @error('institute_state')
                    <small>{{ $message }}</small>
                @enderror


                <label for="institute_district">Institute District</label>

                <input
                    id="institute_district"
                    name="institute_district"
                    type="text"
                    value="{{ old('institute_district', $learner->institute_district) }}"
                >

                @error('institute_district')
                    <small>{{ $message }}</small>
                @enderror


                <label for="unit">Unit</label>

                <input
                    id="unit"
                    name="unit"
                    type="text"
                    value="{{ old('unit', $learner->unit) }}"
                >

                @error('unit')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    SOCIAL INFORMATION
                ====================================================== --}}

                <label for="social_category">Social Category</label>

                <input
                    id="social_category"
                    name="social_category"
                    type="text"
                    value="{{ old('social_category', $learner->social_category) }}"
                >

                @error('social_category')
                    <small>{{ $message }}</small>
                @enderror


                <label for="religion">Religion</label>

                <input
                    id="religion"
                    name="religion"
                    type="text"
                    value="{{ old('religion', $learner->religion) }}"
                >

                @error('religion')
                    <small>{{ $message }}</small>
                @enderror


                <label for="differently_abled">Differently Abled</label>

                <select id="differently_abled" name="differently_abled">

                    <option value="">Select</option>

                    <option value="Yes"
                        @selected(old('differently_abled', $learner->differently_abled) === 'Yes')>
                        Yes
                    </option>

                    <option value="No"
                        @selected(old('differently_abled', $learner->differently_abled) === 'No')>
                        No
                    </option>

                </select>

                @error('differently_abled')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    PROFICIENCY
                ====================================================== --}}

                <label for="digital_proficiency_level">
                    Digital Proficiency Level
                </label>

                <input
                    id="digital_proficiency_level"
                    name="digital_proficiency_level"
                    type="text"
                    value="{{ old('digital_proficiency_level', $learner->digital_proficiency_level) }}"
                >

                @error('digital_proficiency_level')
                    <small>{{ $message }}</small>
                @enderror


                <label for="english_proficiency_level">
                    English Proficiency Level
                </label>

                <input
                    id="english_proficiency_level"
                    name="english_proficiency_level"
                    type="text"
                    value="{{ old('english_proficiency_level', $learner->english_proficiency_level) }}"
                >

                @error('english_proficiency_level')
                    <small>{{ $message }}</small>
                @enderror


                <label for="identity_document">Identity Document</label>

                <input
                    id="identity_document"
                    name="identity_document"
                    type="text"
                    value="{{ old('identity_document', $learner->identity_document) }}"
                >

                @error('identity_document')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    JOB PREFERENCES
                ====================================================== --}}

                <label for="job_preferences_mobility">
                    Job Preferences / Mobility
                </label>

                <input
                    id="job_preferences_mobility"
                    name="job_preferences_mobility"
                    type="text"
                    value="{{ old('job_preferences_mobility', $learner->job_preferences_mobility) }}"
                >

                @error('job_preferences_mobility')
                    <small>{{ $message }}</small>
                @enderror


                <label for="interested_in_job">Interested In Job</label>

                <select id="interested_in_job" name="interested_in_job" required>

                    <option value="">Select</option>

                    <option value="1"
                        @selected((string) old('interested_in_job', $learner->interested_in_job) === '1')>
                        Yes
                    </option>

                    <option value="0"
                        @selected((string) old('interested_in_job', $learner->interested_in_job) === '0')>
                        No
                    </option>

                </select>

                @error('interested_in_job')
                    <small>{{ $message }}</small>
                @enderror


                <label for="residential_area_type">
                    Residential Area Type
                </label>

                <input
                    id="residential_area_type"
                    name="residential_area_type"
                    type="text"
                    value="{{ old('residential_area_type', $learner->residential_area_type) }}"
                >

                @error('residential_area_type')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    PROGRAM / COURSE DETAILS
                ====================================================== --}}

                <label for="completed_skill_programs">
                    Completed Skill Programs
                </label>

                <textarea
                    id="completed_skill_programs"
                    name="completed_skill_programs"
                    rows="3"
                >{{ old('completed_skill_programs', $learner->completed_skill_programs) }}</textarea>

                @error('completed_skill_programs')
                    <small>{{ $message }}</small>
                @enderror


                <label for="currently_enrolled_program">
                    Currently Enrolled Program
                </label>

                <input
                    id="currently_enrolled_program"
                    name="currently_enrolled_program"
                    type="text"
                    value="{{ old('currently_enrolled_program', $learner->currently_enrolled_program) }}"
                >

                @error('currently_enrolled_program')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    STATISTICS
                ====================================================== --}}

                <label for="no_of_pathway_completed">
                    No. of Pathway Completed
                </label>

                <input
                    id="no_of_pathway_completed"
                    name="no_of_pathway_completed"
                    type="number"
                    min="0"
                    value="{{ old('no_of_pathway_completed', $learner->no_of_pathway_completed) }}"
                >

                @error('no_of_pathway_completed')
                    <small>{{ $message }}</small>
                @enderror


                <label for="no_of_pathway_enrolled">
                    No. of Pathway Enrolled
                </label>

                <input
                    id="no_of_pathway_enrolled"
                    name="no_of_pathway_enrolled"
                    type="number"
                    min="0"
                    value="{{ old('no_of_pathway_enrolled', $learner->no_of_pathway_enrolled) }}"
                >

                @error('no_of_pathway_enrolled')
                    <small>{{ $message }}</small>
                @enderror


                <label for="no_of_applied_jobs">
                    No. of Applied Jobs
                </label>

                <input
                    id="no_of_applied_jobs"
                    name="no_of_applied_jobs"
                    type="number"
                    min="0"
                    value="{{ old('no_of_applied_jobs', $learner->no_of_applied_jobs) }}"
                >

                @error('no_of_applied_jobs')
                    <small>{{ $message }}</small>
                @enderror


                <label for="no_of_applied_courses">
                    No. of Applied Courses
                </label>

                <input
                    id="no_of_applied_courses"
                    name="no_of_applied_courses"
                    type="number"
                    min="0"
                    value="{{ old('no_of_applied_courses', $learner->no_of_applied_courses) }}"
                >

                @error('no_of_applied_courses')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    PARTNER COUNTS
                ====================================================== --}}

                <label for="partner_wise_course_counts">
                    Partner Wise Course Counts
                </label>

                <textarea
                    id="partner_wise_course_counts"
                    name="partner_wise_course_counts"
                    rows="3"
                >{{ old('partner_wise_course_counts', $learner->partner_wise_course_counts) }}</textarea>

                @error('partner_wise_course_counts')
                    <small>{{ $message }}</small>
                @enderror


                <label for="partner_wise_job_counts">
                    Partner Wise Job Counts
                </label>

                <textarea
                    id="partner_wise_job_counts"
                    name="partner_wise_job_counts"
                    rows="3"
                >{{ old('partner_wise_job_counts', $learner->partner_wise_job_counts) }}</textarea>

                @error('partner_wise_job_counts')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    PROFILE
                ====================================================== --}}

                <label for="profile_photo_url">
                    Profile Photo URL
                </label>

                <input
                    id="profile_photo_url"
                    name="profile_photo_url"
                    type="url"
                    value="{{ old('profile_photo_url', $learner->profile_photo_url) }}"
                >

                @error('profile_photo_url')
                    <small>{{ $message }}</small>
                @enderror

                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="active" @selected(old('status', $learner->status ?: 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $learner->status) === 'inactive')>Inactive</option>
                </select>
                @error('status')
                    <small>{{ $message }}</small>
                @enderror


                {{-- =====================================================
                    FORM ACTIONS
                ====================================================== --}}

                <div class="admin-partners-form-actions">

                    <a href="{{ route('admin.learners.index') }}">
                        Cancel
                    </a>

                    <button type="submit">
                        {{ $learner->exists ? 'Save changes' : 'Create learner' }}
                    </button>

                </div>

            </form>

        </section>
    </section>

</main>


@endsection
