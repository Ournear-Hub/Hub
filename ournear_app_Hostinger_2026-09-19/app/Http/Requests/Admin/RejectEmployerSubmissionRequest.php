<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployerSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectEmployerSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof EmployerSubmission
            && $this->user()?->can('update', $submission) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
            'rejection_reason_id' => [
                'nullable',
                'integer',
                Rule::exists('rejection_reasons', 'id')->where('is_active', true),
            ],
        ];
    }
}
