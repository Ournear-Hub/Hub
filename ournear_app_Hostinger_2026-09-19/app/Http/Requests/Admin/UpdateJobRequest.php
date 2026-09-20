<?php

namespace App\Http\Requests\Admin;

use App\Enums\JobPublishingStatus;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'project' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', 'max:255'],
            'experience' => ['required', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'vacancy' => ['required', 'integer', 'min:1', 'max:9999'],
            'posted_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:posted_date'],
            'featured' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'publishing_status' => ['required', Rule::enum(JobPublishingStatus::class)],
            'description' => ['required', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'what_we_offer' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
