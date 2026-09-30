<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Learner extends Model
{


 protected $table = 'learners';

    protected $fillable = [
        'normalized_mobile',
        'phone_number',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'education_level',
        'monthly_family_income_range',
        'email',
        'city',
        'state',
        'status',
        'pin_code',

        'program_code',
        'institute_state',
        'institute_district',
        'unit',

        'social_category',
        'religion',
        'marital_status',
        'differently_abled',

        'digital_proficiency_level',
        'english_proficiency_level',
        'identity_document',

        'job_preferences_mobility',
        'profile_photo_url',

        'completed_skill_programs',
        'currently_enrolled_program',
        'residential_area_type',
        'interested_in_job',

        'no_of_pathway_completed',
        'no_of_pathway_enrolled',
        'no_of_applied_jobs',
        'no_of_applied_courses',

        'partner_wise_course_counts',
        'partner_wise_job_counts',

        // Add only if you want these manually assignable
        // 'source_created_at',
        // 'updated_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'interested_in_job' => 'boolean',
        'no_of_pathway_completed' => 'integer',
        'no_of_pathway_enrolled' => 'integer',
        'no_of_applied_jobs' => 'integer',
        'no_of_applied_courses' => 'integer',
    ];



    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function partnerCenter()
    {
        return $this->belongsTo(PartnerCenter::class, 'partner_center_id');
    }


    public function fieldAgent()
    {
        return $this->belongsTo(FieldAgent::class, 'field_agent_id');
    }


    public function eventTransactions()
    {
        return $this->hasMany(
            EventTransaction::class,
            'learner_id'
        );
    }

}
