<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountStaffingEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessHiringWorkspace() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'project_location' => ['required', 'string', 'max:255'],
            'workforce_type' => ['required', 'string', 'max:255'],
            'number_of_people' => ['required', 'integer', 'min:1', 'max:9999'],
            'required_skills' => ['required', 'string', 'max:5000'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'training_required' => ['required', Rule::in(['0', '1', 0, 1, true, false, 'true', 'false'])],
            'additional_requirements' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
