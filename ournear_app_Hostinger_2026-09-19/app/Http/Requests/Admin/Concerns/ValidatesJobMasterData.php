<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobTitle;
use App\Models\Location;
use Illuminate\Validation\Validator;

trait ValidatesJobMasterData
{
    /**
     * @return array<string, mixed>
     */
    protected function jobMasterRules(bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'industry_id' => [$presence, 'integer', 'exists:industries,id'],
            'job_category_id' => [$presence, 'integer', 'exists:job_categories,id'],
            'job_title_id' => [$presence, 'integer', 'exists:job_titles,id'],
            'location_id' => [$presence, 'integer', 'exists:locations,id'],
            'employment_type_id' => [$presence, 'integer', 'exists:employment_types,id'],
            'experience_range_id' => [$presence, 'integer', 'exists:experience_ranges,id'],
        ];
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
}
