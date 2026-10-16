<?php

namespace App\Http\Requests\Admin;

use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Support\OurNearInternalRecruiters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruiterTalentPoolAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\RecruiterTalentPoolAssignment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'candidate_user_id' => ['required_without:candidate_user_ids', 'integer', 'exists:users,id'],
            'candidate_user_ids' => ['required_without:candidate_user_id', 'array', 'min:1'],
            'candidate_user_ids.*' => ['integer', 'exists:users,id'],
            'assigned_recruiter_user_id' => OurNearInternalRecruiters::assignedRecruiterIdRules(),
            'assignment_type' => ['required', 'string', Rule::enum(RecruiterTalentPoolAssignmentType::class)],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'job_id' => ['nullable', 'integer', 'exists:job_listings,id'],
            'employer_enquiry_id' => ['nullable', 'integer', 'exists:employer_enquiries,id'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
            'return_candidate_profile_id' => ['nullable', 'integer', 'exists:candidate_profiles,id'],
        ];
    }
}
