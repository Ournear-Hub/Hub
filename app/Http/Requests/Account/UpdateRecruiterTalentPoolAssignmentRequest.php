<?php

namespace App\Http\Requests\Account;

use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecruiterTalentPoolAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $assignment instanceof \App\Models\RecruiterTalentPoolAssignment
            && ($this->user()?->can('updateStatus', $assignment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(RecruiterTalentPoolAssignmentStatus::class)],
            'progress_stage' => ['nullable', 'string', Rule::enum(RecruiterTalentPoolAssignmentProgressStage::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_at' => ['nullable', 'date'],
            'interview_at' => ['nullable', 'date'],
            'interview_details' => ['nullable', 'string', 'max:5000'],
        ];
    }

}
