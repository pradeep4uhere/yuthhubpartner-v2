@extends('layouts.app')

@section('content')
<main class="admin-partners-page">
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

        <section class="admin-partners-form-panel">
            <form method="POST" action="{{ $formAction }}" class="admin-partners-form">
                @csrf

                @if($formMethod !== 'POST')
                    @method($formMethod)
                @endif

                {{-- Mobile --}}
                <label for="normalized_mobile">Normalized Mobile</label>
                <input
                    id="normalized_mobile"
                    name="normalized_mobile"
                    value="{{ old('normalized_mobile', $learner->normalized_mobile) }}"
                    required>
                @error('normalized_mobile')
                    <small>{{ $message }}</small>
                @enderror

                <label for="phone_number">Phone Number</label>
                <input
                    id="phone_number"
                    name="phone_number"
                    value="{{ old('phone_number', $learner->phone_number) }}">
                @error('phone_number')
                    <small>{{ $message }}</small>
                @enderror

                {{-- Name --}}
                <label for="first_name">First Name</label>
                <input
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name', $learner->first_name) }}">
                @error('first_name')
                    <small>{{ $message }}</small>
                @enderror

                <label for="last_name">Last Name</label>
                <input
                    id="last_name"
                    name="last_name"
                    value="{{ old('last_name', $learner->last_name) }}">
                @error('last_name')
                    <small>{{ $message }}</small>
                @enderror

                {{-- Gender --}}
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select</option>
                    <option value="Male" @selected(old('gender',$learner->gender)=='Male')>Male</option>
                    <option value="Female" @selected(old('gender',$learner->gender)=='Female')>Female</option>
                    <option value="Other" @selected(old('gender',$learner->gender)=='Other')>Other</option>
                </select>

                <label for="date_of_birth">Date of Birth</label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', $learner->date_of_birth) }}">

                {{-- Education --}}
                <label for="education_level">Education Level</label>
                <input
                    id="education_level"
                    name="education_level"
                    value="{{ old('education_level', $learner->education_level) }}">

                <label for="monthly_family_income_range">Monthly Family Income Range</label>
                <input
                    id="monthly_family_income_range"
                    name="monthly_family_income_range"
                    value="{{ old('monthly_family_income_range', $learner->monthly_family_income_range) }}">

                {{-- Email --}}
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $learner->email) }}">

                {{-- Address --}}
                <label for="city">City</label>
                <input
                    id="city"
                    name="city"
                    value="{{ old('city', $learner->city) }}">

                <label for="state">State</label>
                <input
                    id="state"
                    name="state"
                    value="{{ old('state', $learner->state) }}">

                <label for="pin_code">Pin Code</label>
                <input
                    id="pin_code"
                    name="pin_code"
                    value="{{ old('pin_code', $learner->pin_code) }}">

                {{-- Program --}}
                <label for="program_code">Program Code</label>
                <input
                    id="program_code"
                    name="program_code"
                    value="{{ old('program_code', $learner->program_code) }}">

                <label for="institute_state">Institute State</label>
                <input
                    id="institute_state"
                    name="institute_state"
                    value="{{ old('institute_state', $learner->institute_state) }}">

                <label for="institute_district">Institute District</label>
                <input
                    id="institute_district"
                    name="institute_district"
                    value="{{ old('institute_district', $learner->institute_district) }}">

                <label for="unit">Unit</label>
                <input
                    id="unit"
                    name="unit"
                    value="{{ old('unit', $learner->unit) }}">

                {{-- Demographics --}}
                <label for="social_category">Social Category</label>
                <input
                    id="social_category"
                    name="social_category"
                    value="{{ old('social_category', $learner->social_category) }}">

                <label for="religion">Religion</label>
                <input
                    id="religion"
                    name="religion"
                    value="{{ old('religion', $learner->religion) }}">

                <label for="marital_status">Marital Status</label>
                <input
                    id="marital_status"
                    name="marital_status"
                    value="{{ old('marital_status', $learner->marital_status) }}">

                <label for="differently_abled">Differently Abled</label>
                <input
                    id="differently_abled"
                    name="differently_abled"
                    value="{{ old('differently_abled', $learner->differently_abled) }}">

                {{-- Skills --}}
                <label for="digital_proficiency_level">Digital Proficiency</label>
                <input
                    id="digital_proficiency_level"
                    name="digital_proficiency_level"
                    value="{{ old('digital_proficiency_level', $learner->digital_proficiency_level) }}">

                <label for="english_proficiency_level">English Proficiency</label>
                <input
                    id="english_proficiency_level"
                    name="english_proficiency_level"
                    value="{{ old('english_proficiency_level', $learner->english_proficiency_level) }}">

                <label for="identity_document">Identity Document</label>
                <input
                    id="identity_document"
                    name="identity_document"
                    value="{{ old('identity_document', $learner->identity_document) }}">

                <label for="job_preferences_mobility">Job Preference Mobility</label>
                <input
                    id="job_preferences_mobility"
                    name="job_preferences_mobility"
                    value="{{ old('job_preferences_mobility', $learner->job_preferences_mobility) }}">

                <label for="profile_photo_url">Profile Photo URL</label>
                <input
                    id="profile_photo_url"
                    name="profile_photo_url"
                    value="{{ old('profile_photo_url', $learner->profile_photo_url) }}">

                <label for="completed_skill_programs">Completed Skill Programs</label>
                <textarea
                    id="completed_skill_programs"
                    name="completed_skill_programs">{{ old('completed_skill_programs', $learner->completed_skill_programs) }}</textarea>

                <label for="currently_enrolled_program">Currently Enrolled Program</label>
                <input
                    id="currently_enrolled_program"
                    name="currently_enrolled_program"
                    value="{{ old('currently_enrolled_program', $learner->currently_enrolled_program) }}">

                <label for="residential_area_type">Residential Area Type</label>
                <input
                    id="residential_area_type"
                    name="residential_area_type"
                    value="{{ old('residential_area_type', $learner->residential_area_type) }}">

                <label for="interested_in_job">Interested In Job</label>
                <select id="interested_in_job" name="interested_in_job">
                    <option value="1" @selected(old('interested_in_job',$learner->interested_in_job)==1)>Yes</option>
                    <option value="0" @selected(old('interested_in_job',$learner->interested_in_job)==0)>No</option>
                </select>

                {{-- Statistics --}}
                <label for="no_of_pathway_completed">Pathways Completed</label>
                <input type="number" id="no_of_pathway_completed" name="no_of_pathway_completed"
                       value="{{ old('no_of_pathway_completed', $learner->no_of_pathway_completed) }}">

                <label for="no_of_pathway_enrolled">Pathways Enrolled</label>
                <input type="number" id="no_of_pathway_enrolled" name="no_of_pathway_enrolled"
                       value="{{ old('no_of_pathway_enrolled', $learner->no_of_pathway_enrolled) }}">

                <label for="no_of_applied_jobs">Applied Jobs</label>
                <input type="number" id="no_of_applied_jobs" name="no_of_applied_jobs"
                       value="{{ old('no_of_applied_jobs', $learner->no_of_applied_jobs) }}">

                <label for="no_of_applied_courses">Applied Courses</label>
                <input type="number" id="no_of_applied_courses" name="no_of_applied_courses"
                       value="{{ old('no_of_applied_courses', $learner->no_of_applied_courses) }}">

                <label for="partner_wise_course_counts">Partner Wise Course Counts</label>
                <textarea id="partner_wise_course_counts"
                          name="partner_wise_course_counts">{{ old('partner_wise_course_counts', $learner->partner_wise_course_counts) }}</textarea>

                <label for="partner_wise_job_counts">Partner Wise Job Counts</label>
                <textarea id="partner_wise_job_counts"
                          name="partner_wise_job_counts">{{ old('partner_wise_job_counts', $learner->partner_wise_job_counts) }}</textarea>

                <div class="admin-partners-form-actions">
                    <a href="{{ route('admin.learners.index') }}">Cancel</a>
                    <button type="submit">
                        {{ $learner->exists ? 'Save Changes' : 'Create Learner' }}
                    </button>
                </div>

            </form>
        </section>
    </section>
</main>
@endsection
