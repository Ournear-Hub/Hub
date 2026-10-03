<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterEntityType;
use App\Models\JobCategory;
use App\Rules\UniqueMasterName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMasterRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->entityType();

        return $type !== null && $this->user()?->can('create', $type->modelClass()) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->entityType();

        if ($type === null) {
            return [];
        }

        $rules = [
            'name' => ['required', 'string', 'max:255', $this->uniqueNameRule($type)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];

        if ($type === MasterEntityType::Categories) {
            $rules['industry_id'] = [
                'required',
                'integer',
                Rule::exists('industries', 'id')->where('is_active', true),
            ];
        }

        if ($type === MasterEntityType::JobTitles) {
            $rules['job_category_id'] = ['required', 'integer', 'exists:job_categories,id'];
            $rules['industry_id'] = ['nullable', 'integer', 'exists:industries,id'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->entityType() !== MasterEntityType::JobTitles || $validator->errors()->isNotEmpty()) {
                return;
            }

            $category = JobCategory::query()
                ->with('industry')
                ->find($this->integer('job_category_id'));

            if ($category === null) {
                $validator->errors()->add('job_category_id', 'Select a valid category.');

                return;
            }

            if (! $category->isSelectable()) {
                $validator->errors()->add('job_category_id', 'Select an active category in an active industry.');
            }

            $industryId = $this->integer('industry_id');
            if ($industryId > 0 && $category->industry_id !== $industryId) {
                $validator->errors()->add('job_category_id', 'This category does not belong to the selected industry.');
            }
        });
    }

    public function entityType(): ?MasterEntityType
    {
        return MasterEntityType::tryFrom((string) $this->route('type'));
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name')) {
            $merge['name'] = trim((string) $this->input('name'));
        }

        foreach (['industry_id', 'job_category_id', 'sort_order'] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }

    private function uniqueNameRule(MasterEntityType $type): UniqueMasterName
    {
        $table = (new ($type->modelClass())())->getTable();
        $scope = [];

        if ($type === MasterEntityType::Categories) {
            $scope['industry_id'] = $this->integer('industry_id');
        }

        if ($type === MasterEntityType::JobTitles) {
            $scope['job_category_id'] = $this->integer('job_category_id');
        }

        return new UniqueMasterName($table, $scope);
    }
}
