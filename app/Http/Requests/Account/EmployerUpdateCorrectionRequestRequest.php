<?php

namespace App\Http\Requests\Account;

use App\Enums\ApplicationCorrectionRequestStatus;
use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployerUpdateCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $this->user()?->can('manageAsEmployer', $application) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(ApplicationCorrectionRequestStatus::class),
                Rule::notIn([ApplicationCorrectionRequestStatus::Pending->value]),
            ],
            'response_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
