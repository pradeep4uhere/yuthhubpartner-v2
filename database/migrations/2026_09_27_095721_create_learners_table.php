<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('learners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partner_id')->nullable()->index();
            $table->foreignId('partner_center_id')->nullable()->index();
            $table->foreignId('field_agent_id')
            ->nullable()
            ->constrained('field_agents')
            ->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])
            ->default('active')
            ->index();
            $table->boolean('course_completed')
            ->default(false);
            $table->string('program_code')->nullable()->index();
            $table->string('phone_number', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('education_level')->nullable();
            $table->string('monthly_family_income_range')->nullable();
            $table->string('social_category')->nullable();
            $table->string('religion')->nullable();
            $table->string('marital_status')->nullable();
            $table->boolean('differently_abled')->default(false);
            $table->string('digital_proficiency_level')->nullable();
            $table->string('english_proficiency_level')->nullable();
            $table->text('identity_document')->nullable();
            $table->text('reason_for_learning_new_skills')->nullable();
            $table->boolean('earn_at_my_own_time')->default(false);
            $table->integer('earn_at_my_own_time_hours_per_day')->nullable();
            $table->text('earn_at_my_own_time_nature_of_work')->nullable();
            $table->text('specific_skill')->nullable();
            $table->string('job_preferences_mobility')->nullable();
            $table->string('get_a_job_qualification')->nullable();
            $table->string('get_a_job_when_can_start')->nullable();
            $table->string('get_a_job_experience')->nullable();
            $table->boolean('run_a_business')->default(false);
            $table->string('run_a_business_status')->nullable();
            $table->text('run_a_business_need_help_with')->nullable();
            $table->string('profile_photo_url')->nullable();
            $table->text('completed_skill_programs')->nullable();
            $table->string('currently_enrolled_program')->nullable();
            $table->string('employment_experience_years')->nullable();
            $table->string('residential_area_type')->nullable();
            $table->boolean('interested_in_job')->default(false);
            $table->string('has_work_experience')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('institute_state')->nullable();
            $table->string('institute_district')->nullable();
            $table->string('unit')->nullable();
            $table->integer('no_of_pathway_completed')->default(0);
            $table->integer('no_of_pathway_enrolled')->default(0);
            $table->integer('no_of_applied_jobs')->default(0);
            $table->integer('no_of_applied_courses')->default(0);
            $table->timestamp('source_created_at')->nullable();
            $table->string('normalized_mobile', 10)
            ->nullable()
            ->index();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};
