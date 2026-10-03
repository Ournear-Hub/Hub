<?php

namespace App\Support;

use App\Enums\JobPublishingStatus;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class JobListingQuery
{
    /**
     * @return LengthAwarePaginator<int, Job>
     */
    public function publicIndex(Request $request): LengthAwarePaginator
    {
        $query = Job::query()->with('employer.user')->publiclyVisible()->latest('posted_date');

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('project', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->orWhere('location', 'like', '%'.$keyword.'%');
            });
        }

        foreach (['location', 'industry', 'category', 'employment_type', 'experience'] as $field) {
            $value = trim((string) $request->query($field, ''));
            if ($value !== '') {
                $query->where($field, $value);
            }
        }

        $company = trim((string) $request->query('company', ''));
        if ($company !== '') {
            $query->whereHas('employer', function ($employer) use ($company) {
                $employer->where('company_name', 'like', '%'.$company.'%');
            });
        }

        $salary = trim((string) $request->query('salary', ''));
        if ($salary !== '') {
            $query->where('salary', 'like', '%'.$salary.'%');
        }

        $workMode = trim((string) $request->query('work_mode', ''));
        if ($workMode !== '') {
            $query->where(function ($inner) use ($workMode) {
                $inner->where('employment_type', 'like', '%'.$workMode.'%')
                    ->orWhere('site', 'like', '%'.$workMode.'%')
                    ->orWhere('description', 'like', '%'.$workMode.'%');
            });
        }

        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        $sort = trim((string) $request->query('sort', ''));
        match ($sort) {
            'oldest' => $query->reorder()->oldest('posted_date'),
            'title' => $query->reorder()->orderBy('title'),
            default => null,
        };

        return $query->paginate(9)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Job>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Job::query()
            ->with([
                'classifiedIndustry',
                'classifiedCategory',
                'classifiedTitle',
                'classifiedLocation',
                'classifiedEmploymentType',
                'classifiedExperienceRange',
                'employer.user',
            ])
            ->withCount('applications')
            ->latest('updated_at');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('business_id', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhere('project', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%')
                    ->orWhereHas('classifiedLocation', fn ($location) => $location->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('employer', function ($employer) use ($search) {
                        $employer->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%')
                            ->orWhere('contact_person', 'like', '%'.$search.'%');
                    });
            });
        }

        $status = (string) $request->query('status', '');
        $allowed = array_map(fn (JobPublishingStatus $case) => $case->value, JobPublishingStatus::cases());
        if (in_array($status, $allowed, true)) {
            $query->where('publishing_status', $status);
        }

        $this->applyMasterOrLegacyFilter($query, $request, 'industry_id', 'industry', Industry::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'location_id', 'location', Location::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'employment_type_id', 'employment_type', EmploymentType::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'experience_range_id', 'experience', ExperienceRange::class);

        $categoryId = $request->integer('job_category_id');
        if ($categoryId > 0) {
            $query->where('job_category_id', $categoryId);
        }

        $titleId = $request->integer('job_title_id');
        if ($titleId > 0) {
            $query->where('job_title_id', $titleId);
        }

        $featured = $request->query('featured');
        if ($featured === '1' || $featured === '0') {
            $query->where('featured', $featured === '1');
        }

        $active = $request->query('active');
        if ($active === '1' || $active === '0') {
            $query->where('active', $active === '1');
        }

        $employerId = $request->integer('employer_id');
        if ($employerId > 0) {
            $query->where('employer_id', $employerId);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return list<Job>
     */
    public function similar(Job $job, int $limit = 6): array
    {
        return Job::query()
            ->with('employer.user')
            ->publiclyVisible()
            ->where('id', '!=', $job->id)
            ->where(function ($inner) use ($job) {
                $inner->where('industry', $job->industry)
                    ->orWhere('category', $job->category)
                    ->orWhere('location', $job->location);

                if ($job->industry_id) {
                    $inner->orWhere('industry_id', $job->industry_id);
                }
                if ($job->job_category_id) {
                    $inner->orWhere('job_category_id', $job->job_category_id);
                }
                if ($job->location_id) {
                    $inner->orWhere('location_id', $job->location_id);
                }
            })
            ->latest('posted_date')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @return list<Job>
     */
    public function featuredPublic(): array
    {
        return Job::query()
            ->with('employer.user')
            ->publiclyVisible()
            ->where('featured', true)
            ->latest('posted_date')
            ->get()
            ->all();
    }

    /**
     * @return array<string, list<string>>
     */
    public function publicFilterOptions(): array
    {
        $base = Job::query()->publiclyVisible();

        return [
            'locations' => $this->distinctValues((clone $base), 'location'),
            'industries' => $this->distinctValues((clone $base), 'industry'),
            'categories' => $this->distinctValues((clone $base), 'category'),
            'employment_types' => $this->distinctValues((clone $base), 'employment_type'),
            'experiences' => $this->distinctValues((clone $base), 'experience'),
            'companies' => $this->distinctCompanyNames(),
            'salaries' => $this->distinctValues((clone $base), 'salary'),
        ];
    }

    /**
     * @return list<string>
     */
    private function distinctCompanyNames(): array
    {
        return Job::query()
            ->publiclyVisible()
            ->with('employer')
            ->get()
            ->map(fn (Job $job) => trim((string) $job->employer?->company_name))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Job>  $query
     * @param  class-string  $model
     */
    private function applyMasterOrLegacyFilter($query, Request $request, string $idColumn, string $legacyColumn, string $model): void
    {
        $id = $request->integer($idColumn);
        if ($id <= 0) {
            return;
        }

        $name = $model::query()->whereKey($id)->value('name');

        $query->where(function ($inner) use ($id, $idColumn, $legacyColumn, $name) {
            $inner->where($idColumn, $id);

            if (is_string($name) && $name !== '') {
                $inner->orWhere(function ($legacy) use ($idColumn, $legacyColumn, $name) {
                    $legacy->whereNull($idColumn)->where($legacyColumn, $name);
                });
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Job>  $query
     * @return list<string>
     */
    private function distinctValues($query, string $column): array
    {
        return $query
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter()
            ->values()
            ->all();
    }
}
