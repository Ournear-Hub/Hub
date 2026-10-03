<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AutomationActionType;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use App\Models\RecruitmentAutomation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecruitmentAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $automation = $this->route('automation');

        return $automation instanceof RecruitmentAutomation
            && $this->user()?->can('update', $automation) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'trigger' => ['required', Rule::enum(AutomationTrigger::class)],
            'scope' => ['required', Rule::enum(AutomationScope::class)],
            'job_id' => ['nullable', 'integer', 'exists:job_listings,id'],
            'employer_profile_id' => ['nullable', 'integer', 'exists:employer_profiles,id'],
            'is_enabled' => ['nullable', 'boolean'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*' => ['required', 'string', Rule::enum(AutomationActionType::class)],
            'conditions' => ['nullable', 'array'],
            'conditions.min_match_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'conditions.application_status' => ['nullable', 'string', Rule::enum(ApplicationStatus::class)],
            'conditions.application_origin' => ['nullable', 'string', 'in:guest,account'],
            'conditions.delay_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'conditions.allow_insufficient_data' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $conditions = $this->input('conditions', []);
        if (is_array($conditions)) {
            foreach (['min_match_percent', 'application_status', 'delay_hours', 'application_origin'] as $field) {
                if (($conditions[$field] ?? null) === '') {
                    $conditions[$field] = null;
                }
            }
            $this->merge(['conditions' => $conditions]);
        }

        foreach (['description', 'job_id', 'employer_profile_id'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
