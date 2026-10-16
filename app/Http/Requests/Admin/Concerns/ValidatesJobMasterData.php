<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Enums\JobPublishingStatus;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\Skill;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesJobMasterData
{
    /**
     * @return array<string, mixed>
     */
    protected function isEmployerAccountJobRequest(): bool
    {
        $user = $this->user();

        return $user !== null
            && ! $user->isAdmin()
            && $user->canAccessHiringWorkspace();
    }

    protected function mergeEmployerIndustryFromProfile(): void
    {
        if (! $this->isEmployerAccountJobRequest()) {
            return;
        }

        $profile = $this->user()?->employerProfile;
        if ($profile?->industry_id) {
            $this->merge(['industry_id' => $profile->industry_id]);
        }
    }

    protected function jobMasterRules(bool $required, bool $requireJobTitle = true): array
    {
        $presence = $required ? 'required' : 'nullable';
        $titlePresence = ($required && $requireJobTitle) ? 'required' : 'nullable';

        return [
            'industry_id' => [$presence, 'integer', 'min:1', Rule::exists('industries', 'id')],
            'job_category_id' => [$presence, 'integer', 'min:1', Rule::exists('job_categories', 'id')],
            'job_title_id' => [$titlePresence, 'nullable', 'integer', 'min:1', Rule::exists('job_titles', 'id')],
            'location_id' => [$presence, 'integer', 'min:1', Rule::exists('locations', 'id')],
            'employment_type_id' => [$presence, 'integer', 'min:1', Rule::exists('employment_types', 'id')],
            'experience_range_id' => [$presence, 'integer', 'min:1', Rule::exists('experience_ranges', 'id')],
            'employer_id' => ['nullable', 'integer', 'min:1', Rule::exists('employer_profiles', 'id')],
        ];
    }

    protected function validateEmployerAssignment(Validator $validator): void
    {
        $user = $this->user();
        if ($user === null || $user->isAdmin()) {
            return;
        }

        if (! $user->canAccessHiringWorkspace()) {
            return;
        }

        $submittedEmployerId = $this->input('employer_id');
        if ($submittedEmployerId === null || $submittedEmployerId === '') {
            return;
        }

        $ownedProfileId = $user->employerProfile?->id;
        if ($ownedProfileId === null || (int) $submittedEmployerId !== (int) $ownedProfileId) {
            $validator->errors()->add('employer_id', 'You cannot assign this job to another company.');
        }
    }

    protected function validateEmployerPublishMasterFields(Validator $validator): void
    {
        if (! $this->isEmployerAccountJobRequest()) {
            return;
        }

        $status = JobPublishingStatus::tryFrom((string) $this->input('publishing_status', ''));
        if ($status !== JobPublishingStatus::Published) {
            return;
        }

        if ($this->integer('job_category_id') <= 0) {
            $validator->errors()->add('job_category_id', 'Select a job category before publishing.');
        }

        if ($this->integer('location_id') <= 0) {
            $validator->errors()->add('location_id', 'Select a location before publishing.');
        }

        if ($this->integer('employment_type_id') <= 0) {
            $validator->errors()->add('employment_type_id', 'Select an employment type before publishing.');
        }

        if ($this->integer('experience_range_id') <= 0) {
            $validator->errors()->add('experience_range_id', 'Select an experience range before publishing.');
        }
    }

    protected function validateJobMasterHierarchy(Validator $validator, bool $requireSelectable): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $industryId = $this->integer('industry_id');
        $categoryId = $this->integer('job_category_id');
        $titleId = $this->integer('job_title_id');

        if ($industryId > 0) {
            $industry = Industry::query()->find($industryId);
            $currentId = $this->currentJob()?->industry_id;
            if ($industry && $requireSelectable && (int) $currentId !== $industryId && ! $industry->isSelectable()) {
                $validator->errors()->add('industry_id', 'Select an active industry.');
            }
        }

        if ($categoryId > 0) {
            $category = JobCategory::query()->with('industry')->find($categoryId);
            if ($category === null) {
                $validator->errors()->add('job_category_id', 'Select a valid job category.');
            } else {
                if ($industryId > 0 && (int) $category->industry_id !== $industryId) {
                    $validator->errors()->add('job_category_id', 'This category does not belong to the selected industry.');
                }

                $currentId = $this->currentJob()?->job_category_id;
                if ($requireSelectable && (int) $currentId !== $categoryId && ! $category->isSelectable()) {
                    $validator->errors()->add('job_category_id', 'Select an active category in an active industry.');
                }
            }
        }

        if ($titleId > 0) {
            $title = JobTitle::query()->with('category.industry')->find($titleId);
            if ($title === null) {
                $validator->errors()->add('job_title_id', 'Select a valid job title.');
            } else {
                if ($categoryId > 0 && (int) $title->job_category_id !== $categoryId) {
                    $validator->errors()->add('job_title_id', 'This job title does not belong to the selected category.');
                }

                if ($industryId > 0 && $title->category && (int) $title->category->industry_id !== $industryId) {
                    $validator->errors()->add('job_title_id', 'This job title does not belong to the selected industry.');
                }

                $currentId = $this->currentJob()?->job_title_id;
                if ($requireSelectable && (int) $currentId !== $titleId && ! $title->isSelectable()) {
                    $validator->errors()->add('job_title_id', 'Select an active job title in an active category.');
                }
            }
        }

        $this->validateSelectableLookup($validator, 'location_id', Location::class, $this->currentJob()?->location_id, $requireSelectable);
        $this->validateSelectableLookup($validator, 'employment_type_id', EmploymentType::class, $this->currentJob()?->employment_type_id, $requireSelectable);
        $this->validateSelectableLookup($validator, 'experience_range_id', ExperienceRange::class, $this->currentJob()?->experience_range_id, $requireSelectable);
    }

    /**
     * @param  class-string  $model
     */
    private function validateSelectableLookup(Validator $validator, string $field, string $model, mixed $currentId, bool $requireSelectable): void
    {
        $id = $this->integer($field);
        if ($id <= 0 || ! $requireSelectable || (int) $currentId === $id) {
            return;
        }

        $record = $model::query()->find($id);
        if ($record && method_exists($record, 'isSelectable') && ! $record->isSelectable()) {
            $validator->errors()->add($field, 'Select an active value.');
        }
    }

    protected function currentJob(): ?Job
    {
        $job = $this->route('job');

        return $job instanceof Job ? $job : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function jobRequirementRules(): array
    {
        return [
            'qualification_ids' => ['nullable', 'array', 'max:15', 'distinct'],
            'qualification_ids.*' => ['integer', 'min:1', 'distinct', Rule::exists('qualifications', 'id')],
            'skill_ids' => ['nullable', 'array', 'max:25', 'distinct'],
            'skill_ids.*' => ['integer', 'min:1', 'distinct', Rule::exists('skills', 'id')],
        ];
    }

    protected function validateJobRequirements(Validator $validator, bool $requireSelectable): void
    {
        $currentQualificationIds = $this->currentJob()?->qualifications()->pluck('qualifications.id')->all() ?? [];
        $currentQualificationIds = array_map(intval(...), $currentQualificationIds);

        if ($this->has('qualification_ids') && ! is_array($this->input('qualification_ids'))) {
            $validator->errors()->add('qualification_ids', 'Qualifications must be a list of valid IDs.');

            return;
        }

        if ($this->has('skill_ids') && ! is_array($this->input('skill_ids'))) {
            $validator->errors()->add('skill_ids', 'Skills must be a list of valid IDs.');

            return;
        }

        foreach ($this->input('qualification_ids', []) as $index => $rawId) {
            if (! is_numeric($rawId)) {
                $validator->errors()->add("qualification_ids.{$index}", 'Each qualification must be a valid ID.');

                continue;
            }

            $id = (int) $rawId;
            if ($id <= 0) {
                $validator->errors()->add("qualification_ids.{$index}", 'Select a valid qualification.');

                continue;
            }

            $qualification = Qualification::query()->find($id);
            if ($qualification === null) {
                $validator->errors()->add("qualification_ids.{$index}", 'Select a valid qualification.');

                continue;
            }

            if ($requireSelectable && ! in_array($id, $currentQualificationIds, true) && ! $qualification->isSelectable()) {
                $validator->errors()->add("qualification_ids.{$index}", 'Select an active qualification.');
            }
        }

        foreach ($this->input('skill_ids', []) as $index => $rawId) {
            if (! is_numeric($rawId)) {
                $validator->errors()->add("skill_ids.{$index}", 'Each skill must be a valid ID.');

                continue;
            }

            $id = (int) $rawId;
            if ($id <= 0) {
                $validator->errors()->add("skill_ids.{$index}", 'Select a valid skill.');

                continue;
            }

            if (! Skill::query()->whereKey($id)->exists()) {
                $validator->errors()->add("skill_ids.{$index}", 'Select a valid skill.');
            }
        }
    }

    protected function prepareJobMasterDataForValidation(): void
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
