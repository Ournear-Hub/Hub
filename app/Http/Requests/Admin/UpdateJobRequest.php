<?php

namespace App\Http\Requests\Admin;

use App\Enums\JobPublishingStatus;
use App\Http\Requests\Admin\Concerns\ValidatesJobMasterData;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateJobRequest extends FormRequest
{
    use ValidatesJobMasterData;

    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && $this->user()?->can('update', $job) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employer = $this->isEmployerAccountJobRequest();

        return [
            'title' => ['required', 'string', 'max:255'],
            'project' => [$employer ? 'nullable' : 'required', 'string', 'max:255'],
            ...$this->jobMasterRules(false, requireJobTitle: ! $employer),
            'salary' => ['nullable', 'string', 'max:255'],
            'vacancy' => ['required', 'integer', 'min:1', 'max:9999'],
            'posted_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:posted_date'],
            'featured' => ['sometimes', 'boolean'],
            'urgent' => ['sometimes', 'boolean'],
            'listing_priority' => ['sometimes', 'string', Rule::in(['standard', 'urgent', 'immediate_requirement'])],
            'active' => ['sometimes', 'boolean'],
            'publishing_status' => ['required', Rule::enum(JobPublishingStatus::class)],
            'joining_date' => ['nullable', 'date', 'after_or_equal:posted_date'],
            'site' => ['nullable', 'string', 'max:255'],
            'accommodation' => ['nullable', 'string', 'max:20000'],
            'description' => ['required', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'what_we_offer' => ['nullable', 'string', 'max:20000'],
            ...$this->jobRequirementRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateEmployerAssignment($validator);
            if ($this->isEmployerAccountJobRequest() && ! $this->user()?->employerProfile?->industry_id) {
                $validator->errors()->add(
                    'industry_id',
                    'Set your company industry on Company Profile before saving jobs.',
                );
            }
            $this->validateEmployerPublishMasterFields($validator);
            $this->validateJobMasterHierarchy($validator, true);
            $this->validateJobRequirements($validator, true);
        });
    }

    protected function prepareForValidation(): void
    {
        $this->prepareJobMasterDataForValidation();
        $this->mergeEmployerIndustryFromProfile();

        $merge = [];

        foreach ([
            'closing_date',
            'joining_date',
            'salary',
            'site',
            'accommodation',
        ] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
