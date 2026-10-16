<?php

namespace App\Http\Requests\Account;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployerRejectApplicationRequest extends FormRequest
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
            'rejection_reason_id' => [
                'required',
                'integer',
                Rule::exists('rejection_reasons', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
