<?php

namespace App\Http\Requests\Admin;

use App\Enums\ScreeningDecision;
use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveApplicationScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $this->user()?->can('update', $application) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([
                ScreeningDecision::Suitable->value,
                ScreeningDecision::NotSuitable->value,
                ScreeningDecision::Shortlisted->value,
            ])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rejection_reason_id' => [
                Rule::requiredIf(fn () => $this->input('decision') === ScreeningDecision::NotSuitable->value),
                'nullable',
                'integer',
                Rule::exists('rejection_reasons', 'id')->where('is_active', true),
            ],
        ];
    }
}
