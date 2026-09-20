<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterEntityType;
use App\Models\Industry;
use App\Models\JobCategory;
use App\Rules\UniqueMasterName;
use App\Services\MasterData\MasterDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMasterRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->entityType();

        if ($type === null) {
            return false;
        }

        return $this->user()?->can('update', $this->record()) === true;
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
            'description' => ['nullable', 'string', 'max:2000'],
        ];

        if ($type === MasterEntityType::Categories) {
            $rules['industry_id'] = ['required', 'integer', Rule::exists('industries', 'id')];
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
            $type = $this->entityType();

            if ($type === MasterEntityType::Categories) {
                $industryId = $this->integer('industry_id');
                $current = (int) $this->record()->getAttribute('industry_id');

                if ($industryId > 0 && $industryId !== $current) {
                    $industryIsActive = Industry::query()
                        ->whereKey($industryId)
                        ->where('is_active', true)
                        ->exists();

                    if (! $industryIsActive) {
                        $validator->errors()->add('industry_id', 'Select an active industry.');
                    }
                }
            }

            if ($type !== MasterEntityType::JobTitles || $validator->errors()->isNotEmpty()) {
                return;
            }

            $category = JobCategory::query()
                ->with('industry')
                ->find($this->integer('job_category_id'));

            if ($category === null) {
                $validator->errors()->add('job_category_id', 'Select a valid category.');

                return;
            }

            $currentCategoryId = (int) $this->record()->getAttribute('job_category_id');
            if ($category->id !== $currentCategoryId && ! $category->isSelectable()) {
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

    public function record(): Model
    {
        $type = $this->entityType();

        abort_if($type === null, 404);

        return app(MasterDataService::class)->resolve($type, (int) $this->route('id'));
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

        return new UniqueMasterName($table, $scope, (int) $this->route('id'));
    }
}
