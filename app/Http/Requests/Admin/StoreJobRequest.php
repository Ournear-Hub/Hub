<?php

namespace App\Http\Requests\Admin;

use App\Enums\JobPublishingStatus;
use App\Http\Requests\Admin\Concerns\ValidatesJobMasterData;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJobRequest extends FormRequest
{
    use ValidatesJobMasterData;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Job::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project' => ['required', 'string', 'max:255'],
            ...$this->jobMasterRules(true),
            'salary' => ['nullable', 'string', 'max:255'],
            'vacancy' => ['required', 'integer', 'min:1', 'max:9999'],
            'posted_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:posted_date'],
            'featured' => ['sometimes', 'boolean'],
            'urgent' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'publishing_status' => ['required', Rule::enum(JobPublishingStatus::class)],
            'joining_date' => ['nullable', 'date', 'after_or_equal:posted_date'],
            'site' => ['nullable', 'string', 'max:255'],
            'accommodation' => ['nullable', 'string', 'max:20000'],
            'description' => ['required', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'what_we_offer' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateJobMasterHierarchy($validator, true));
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach ([
            'industry_id',
            'job_category_id',
            'job_title_id',
            'location_id',
            'employment_type_id',
            'experience_range_id',
            'employer_id',
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

        $this->merge($merge);
    }
}
