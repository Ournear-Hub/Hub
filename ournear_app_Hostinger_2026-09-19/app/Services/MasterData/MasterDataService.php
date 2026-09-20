<?php

namespace App\Services\MasterData;

use App\Enums\MasterDataChangeAction;
use App\Enums\MasterEntityType;
use App\Models\ApplicationStatusOption;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\JobStatusOption;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\MasterDataChange;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function __construct(private MasterDataAuditService $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function workspace(Request $request): array
    {
        $type = MasterEntityType::tryFrom((string) $request->string('tab')->toString())
            ?? MasterEntityType::Industries;

        $records = $this->paginate($type, $request);

        return [
            'tab' => $type->value,
            'tabs' => MasterEntityType::navigation(),
            'meta' => [
                'label' => $type->label(),
                'singular' => $type->singular(),
                'allows_create' => $type->allowsCreate(),
                'has_description' => $type->hasDescription(),
                'has_industry_parent' => $type->hasIndustryParent(),
                'has_category_parent' => $type->hasCategoryParent(),
                'has_code' => $type->hasCode(),
            ],
            'records' => $records,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString() ?: 'all',
                'industry_id' => $request->string('industry_id')->toString(),
                'job_category_id' => $request->string('job_category_id')->toString(),
            ],
            'options' => $this->options(),
        ];
    }

    public function create(MasterEntityType $type, array $payload, User $actor): Model
    {
        if (! $type->allowsCreate()) {
            throw ValidationException::withMessages([
                'name' => 'System status values cannot be created here.',
            ]);
        }

        $record = $type->modelClass()::query()->create($this->attributes($type, $payload));
        $record = $this->loadParents($type, $record);

        $this->audit->record(
            $actor,
            $type,
            (int) $record->getKey(),
            MasterDataChangeAction::Created,
            "Created {$type->singular()} '{$record->name}'",
        );

        return $record;
    }

    public function update(MasterEntityType $type, Model $record, array $payload, User $actor): Model
    {
        if ($this->isSystem($record) && $this->nameChanged($record, $payload['name'] ?? null)) {
            throw ValidationException::withMessages([
                'name' => 'System values cannot be renamed. Existing V1 status strings must stay intact.',
            ]);
        }

        $record->fill($this->attributes($type, $payload, $record));
        $record->save();
        $record = $this->loadParents($type, $record);

        $this->audit->record(
            $actor,
            $type,
            (int) $record->getKey(),
            MasterDataChangeAction::Updated,
            "Updated {$type->singular()} '{$record->name}'",
        );

        return $record;
    }

    public function toggleStatus(MasterEntityType $type, Model $record, bool $isActive, User $actor): Model
    {
        if ($this->isSystem($record) && $isActive === false) {
            throw ValidationException::withMessages([
                'is_active' => 'System values cannot be deactivated because existing records still use them.',
            ]);
        }

        if ((bool) $record->getAttribute('is_active') === $isActive) {
            return $record;
        }

        $record->is_active = $isActive;
        $record->save();

        $action = $isActive ? MasterDataChangeAction::Activated : MasterDataChangeAction::Deactivated;
        $verb = $isActive ? 'Activated' : 'Deactivated';

        $this->audit->record(
            $actor,
            $type,
            (int) $record->getKey(),
            $action,
            "{$verb} {$type->singular()} '{$record->name}'",
        );

        return $record;
    }

    public function resolve(MasterEntityType $type, int $id): Model
    {
        return $this->loadParents($type, $type->modelClass()::query()->findOrFail($id));
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(MasterEntityType $type, Request $request): LengthAwarePaginator
    {
        $query = $this->filteredQuery($type, $request);
        $paginator = $query->paginate(25)->withQueryString();
        $usage = $this->usageCounts($type, $paginator->getCollection()->modelKeys());
        $changes = $this->recentChanges($type, $paginator->getCollection()->modelKeys());

        return $paginator->through(fn (Model $record) => $this->serialize(
            $type,
            $this->loadParents($type, $record),
            (int) ($usage[$record->getKey()] ?? 0),
            $changes[$record->getKey()] ?? [],
        ));
    }

    /**
     * @return Builder<Model>
     */
    private function filteredQuery(MasterEntityType $type, Request $request): Builder
    {
        $query = $type->modelClass()::query();

        $search = trim($request->string('search')->toString());
        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $status = $request->string('status')->toString();
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($type === MasterEntityType::Categories) {
            $query->with('industry');
            $industryId = $request->integer('industry_id');
            if ($industryId > 0) {
                $query->where('industry_id', $industryId);
            }
        }

        if ($type === MasterEntityType::JobTitles) {
            $query->with('category.industry');
            $industryId = $request->integer('industry_id');
            $categoryId = $request->integer('job_category_id');
            if ($categoryId > 0) {
                $query->where('job_category_id', $categoryId);
            } elseif ($industryId > 0) {
                $query->whereHas('category', fn (Builder $builder) => $builder->where('industry_id', $industryId));
            }
        }

        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function attributes(MasterEntityType $type, array $payload, ?Model $record = null): array
    {
        $attributes = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'sort_order' => (int) ($payload['sort_order'] ?? $record?->getAttribute('sort_order') ?? 0),
        ];

        if ($record === null) {
            $attributes['is_active'] = array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : true;
        }

        if ($type->hasDescription()) {
            $description = trim((string) ($payload['description'] ?? ''));
            $attributes['description'] = $description === '' ? null : $description;
        }

        if ($type === MasterEntityType::Categories) {
            $attributes['industry_id'] = (int) $payload['industry_id'];
        }

        if ($type === MasterEntityType::JobTitles) {
            $attributes['job_category_id'] = (int) $payload['job_category_id'];
        }

        if ($this->isSystem($record)) {
            unset($attributes['name']);
        }

        return $attributes;
    }

    private function loadParents(MasterEntityType $type, Model $record): Model
    {
        if ($type === MasterEntityType::Categories) {
            $record->loadMissing('industry');
        }

        if ($type === MasterEntityType::JobTitles) {
            $record->loadMissing('category.industry');
        }

        return $record;
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, int>
     */
    private function usageCounts(MasterEntityType $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $counts = array_fill_keys(array_map('intval', $ids), 0);

        foreach ($this->usageSources($type) as [$table, $column]) {
            $rows = DB::table($table)
                ->select($column, DB::raw('count(*) as aggregate'))
                ->whereIn($column, $ids)
                ->groupBy($column)
                ->get();

            foreach ($rows as $row) {
                $key = (int) $row->{$column};
                $counts[$key] = ($counts[$key] ?? 0) + (int) $row->aggregate;
            }
        }

        if ($type === MasterEntityType::ApplicationStatuses) {
            $codes = ApplicationStatusOption::query()->whereIn('id', $ids)->pluck('code', 'id');
            foreach ($codes as $id => $code) {
                $counts[(int) $id] = (int) DB::table('applications')->where('status', $code)->count();
            }
        }

        if ($type === MasterEntityType::JobStatuses) {
            $codes = JobStatusOption::query()->whereIn('id', $ids)->pluck('code', 'id');
            foreach ($codes as $id => $code) {
                $counts[(int) $id] = (int) DB::table('job_listings')->where('publishing_status', $code)->count();
            }
        }

        return $counts;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function usageSources(MasterEntityType $type): array
    {
        return match ($type) {
            MasterEntityType::Industries => [
                ['job_listings', 'industry_id'],
                ['employer_profiles', 'industry_id'],
                ['employer_enquiries', 'industry_id'],
                ['job_categories', 'industry_id'],
            ],
            MasterEntityType::Categories => [
                ['job_titles', 'job_category_id'],
                ['job_listings', 'job_category_id'],
            ],
            MasterEntityType::JobTitles => [
                ['job_listings', 'job_title_id'],
            ],
            MasterEntityType::Locations => [
                ['job_listings', 'location_id'],
                ['applications', 'location_id'],
                ['candidate_profiles', 'location_id'],
                ['employer_enquiries', 'location_id'],
            ],
            MasterEntityType::EmploymentTypes => [
                ['job_listings', 'employment_type_id'],
            ],
            MasterEntityType::ExperienceRanges => [
                ['job_listings', 'experience_range_id'],
                ['applications', 'experience_range_id'],
                ['candidate_profiles', 'experience_range_id'],
            ],
            MasterEntityType::Qualifications => [
                ['applications', 'qualification_id'],
                ['candidate_profiles', 'qualification_id'],
            ],
            default => [],
        };
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, list<array<string, mixed>>>
     */
    private function recentChanges(MasterEntityType $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return MasterDataChange::query()
            ->with('actor')
            ->where('entity_type', $type->value)
            ->whereIn('entity_id', $ids)
            ->latest()
            ->get()
            ->groupBy('entity_id')
            ->map(fn ($group) => $group->take(8)->map->toAdminArray()->values()->all())
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     * @return array<string, mixed>
     */
    private function serialize(MasterEntityType $type, Model $record, int $usageCount, array $changes): array
    {
        $isSystem = $this->isSystem($record);
        $isActive = (bool) $record->getAttribute('is_active');

        $payload = [
            'id' => $record->getKey(),
            'name' => $record->getAttribute('name'),
            'is_active' => $isActive,
            'is_system' => $isSystem,
            'sort_order' => (int) $record->getAttribute('sort_order'),
            'usage_count' => $usageCount,
            'can_edit_name' => ! $isSystem,
            'can_deactivate' => $isActive && ! $isSystem,
            'can_activate' => ! $isActive && ! $isSystem,
            'status_label' => $isActive ? 'Active' : 'Inactive',
            'recent_changes' => $changes,
        ];

        if ($type->hasDescription()) {
            $payload['description'] = $record->getAttribute('description');
        }

        if ($type->hasCode()) {
            $payload['code'] = $record->getAttribute('code');
        }

        if ($type === MasterEntityType::Categories) {
            $payload['industry_id'] = $record->getAttribute('industry_id');
            $payload['industry_name'] = $record->industry?->name;
            $payload['parent_active'] = $record->industry?->is_active ?? false;
        }

        if ($type === MasterEntityType::JobTitles) {
            $payload['job_category_id'] = $record->getAttribute('job_category_id');
            $payload['category_name'] = $record->category?->name;
            $payload['industry_id'] = $record->category?->industry_id;
            $payload['industry_name'] = $record->category?->industry?->name;
            $payload['parent_active'] = $record->category?->isSelectable() ?? false;
        }

        return $payload;
    }

    /**
     * Selectable lookup rows for job/candidate forms.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'industries' => Industry::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (Industry $industry) => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'is_active' => $industry->is_active,
                    'selectable' => $industry->isSelectable(),
                ])
                ->all(),
            'categories' => JobCategory::query()
                ->with('industry:id,name,is_active')
                ->ordered()
                ->get()
                ->map(fn (JobCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'industry_id' => $category->industry_id,
                    'is_active' => $category->is_active,
                    'selectable' => $category->isSelectable(),
                ])
                ->all(),
            'job_titles' => JobTitle::query()
                ->with('category:id,industry_id,is_active')
                ->ordered()
                ->get()
                ->map(fn (JobTitle $title) => [
                    'id' => $title->id,
                    'name' => $title->name,
                    'job_category_id' => $title->job_category_id,
                    'industry_id' => $title->category?->industry_id,
                    'is_active' => $title->is_active,
                    'selectable' => $title->isSelectable(),
                ])
                ->all(),
            'locations' => Location::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'is_active' => $location->is_active,
                    'selectable' => $location->isSelectable(),
                ])
                ->all(),
            'employment_types' => EmploymentType::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (EmploymentType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'is_active' => $type->is_active,
                    'selectable' => $type->isSelectable(),
                ])
                ->all(),
            'experience_ranges' => ExperienceRange::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (ExperienceRange $range) => [
                    'id' => $range->id,
                    'name' => $range->name,
                    'is_active' => $range->is_active,
                    'selectable' => $range->isSelectable(),
                ])
                ->all(),
            'qualifications' => Qualification::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (Qualification $qualification) => [
                    'id' => $qualification->id,
                    'name' => $qualification->name,
                    'is_active' => $qualification->is_active,
                    'selectable' => $qualification->isSelectable(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'industries' => Industry::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (Industry $industry) => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'is_active' => $industry->is_active,
                ])
                ->all(),
            'categories' => JobCategory::query()
                ->with('industry:id,name,is_active')
                ->ordered()
                ->get()
                ->map(fn (JobCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'industry_id' => $category->industry_id,
                    'industry_name' => $category->industry?->name,
                    'is_active' => $category->is_active,
                    'selectable' => $category->isSelectable(),
                ])
                ->all(),
        ];
    }

    private function isSystem(?Model $record): bool
    {
        return $record !== null && (bool) $record->getAttribute('is_system');
    }

    private function nameChanged(Model $record, mixed $name): bool
    {
        return mb_strtolower(trim((string) $name)) !== mb_strtolower(trim((string) $record->getAttribute('name')));
    }
}
