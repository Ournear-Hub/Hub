<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProtectsPublicForms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployerEnquiryRequest extends FormRequest
{
    use ProtectsPublicForms;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            ...$this->publicProtectionRules(),
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'project_location' => ['required', 'string', 'max:255'],
            'workforce_type' => ['required', 'string', 'max:255'],
            'number_of_people' => ['required', 'integer', 'min:1', 'max:9999'],
            'required_skills' => ['required', 'string', 'max:5000'],
            'expected_start_date' => ['nullable', 'date'],
            'period_from' => ['nullable', 'date'],
            'period_to' => ['nullable', 'date'],
            'training_required' => ['required', Rule::in(['0', '1', 0, 1, true, false, 'true', 'false'])],
            'additional_requirements' => ['nullable', 'string', 'max:5000'],
            'from_staffing' => ['nullable', 'string', Rule::in(['0', '1'])],
        ];

        if ($this->input('from_staffing') === '1') {
            $rules['period_from'] = ['required', 'date'];
            $rules['period_to'] = ['required', 'date', 'after_or_equal:period_from'];
            $rules['expected_start_date'] = ['nullable', 'date'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('email')) {
            $merge['email'] = strtolower(trim((string) $this->input('email')));
        }

        if ($this->input('from_staffing') === '1' && $this->filled('period_from')) {
            $merge['expected_start_date'] = $this->input('period_from');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
