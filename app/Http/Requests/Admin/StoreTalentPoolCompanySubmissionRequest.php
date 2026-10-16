<?php

namespace App\Http\Requests\Admin;

use App\Support\OurNearInternalRecruiters;
use Illuminate\Foundation\Http\FormRequest;

class StoreTalentPoolCompanySubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\TalentPoolCompanySubmission::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'candidate_user_id' => ['required', 'integer', 'exists:users,id'],
            'employer_profile_id' => ['required', 'integer', 'exists:employer_profiles,id'],
            'recruiter_talent_pool_assignment_id' => ['nullable', 'integer', 'exists:recruiter_talent_pool_assignments,id'],
            'responsible_recruiter_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $recruiterId = $this->integer('responsible_recruiter_user_id');
            if ($recruiterId <= 0) {
                return;
            }

            $user = \App\Models\User::query()->find($recruiterId);
            if (! $user instanceof \App\Models\User || ! OurNearInternalRecruiters::canActAsInternalRecruiter($user)) {
                $validator->errors()->add('responsible_recruiter_user_id', 'Select an eligible OurNear internal recruiter.');
            }
        });
    }
}
