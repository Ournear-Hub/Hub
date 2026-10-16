<?php

namespace App\Http\Requests\Admin;

use App\Support\OurNearInternalRecruiters;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRelationshipRecruiterAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\CompanyRelationshipRecruiterAssignment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_recruiter_user_id' => OurNearInternalRecruiters::assignedRecruiterIdRules(),
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
