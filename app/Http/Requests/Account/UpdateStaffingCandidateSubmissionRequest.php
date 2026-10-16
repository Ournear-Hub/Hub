<?php

namespace App\Http\Requests\Account;

use App\Enums\StaffingCandidateSubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffingCandidateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('staffingSubmission');

        return $submission instanceof \App\Models\StaffingCandidateSubmission
            && $this->user()?->can('updateClientDecision', $submission) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(StaffingCandidateSubmissionStatus::class)],
            'client_feedback' => ['nullable', 'string', 'max:5000'],
            'rejection_reason_id' => ['nullable', 'integer', 'exists:rejection_reasons,id'],
            'joining_date' => ['nullable', 'date'],
            'joining_confirmation_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
