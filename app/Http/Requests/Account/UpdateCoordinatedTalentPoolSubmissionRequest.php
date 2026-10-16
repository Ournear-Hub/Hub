<?php

namespace App\Http\Requests\Account;

use App\Models\TalentPoolCompanySubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoordinatedTalentPoolSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof TalentPoolCompanySubmission
            && ($this->user()?->can('updateEmployerStatus', $submission) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['review', 'hold', 'select', 'reject'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rejection_reason_id' => ['nullable', 'integer', 'exists:rejection_reasons,id'],
        ];
    }
}
