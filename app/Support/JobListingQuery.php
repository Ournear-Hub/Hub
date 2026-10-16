<?php

namespace App\Support;

use App\Enums\JobPublishingStatus;
use App\Models\EmployerProfile;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class JobListingQuery
{
    /**
     * @return LengthAwarePaginator<int, Job>
     */
    public function publicIndex(Request $request): LengthAwarePaginator
    {
        return $this->paginatePublic($request, 9);
    }

    /**
     * Public job listings for mobile JSON API (same filters as {@see publicIndex()}).
     *
     * @return LengthAwarePaginator<int, Job>
     */
    public function publicApiIndex(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginatePublic($request, $perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Job>
     */
    private function paginatePublic(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = $this->publicListingQuery($request);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Job>
     */
    private function publicListingQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Job::query()
            ->with($this->publicJobRelations())
            ->publiclyVisible()
            ->latest('posted_date');

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('project', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->orWhere('location', 'like', '%'.$keyword.'%')
                    ->orWhereHas('employer', function ($employer) use ($keyword) {
                        $employer->where('company_name', 'like', '%'.$keyword.'%')
                            ->orWhere('business_id', 'like', '%'.$keyword.'%')
                            ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$keyword.'%'));
                    });
            });
        }

        $this->applyPublicNameFilter($query, 'location', 'classifiedLocation', (string) $request->query('location', ''));
        $this->applyPublicNameFilter($query, 'industry', 'classifiedIndustry', (string) $request->query('industry', ''));
        $this->applyPublicNameFilter($query, 'category', 'classifiedCategory', (string) $request->query('category', ''));
        $this->applyPublicNameFilter($query, 'employment_type', 'classifiedEmploymentType', (string) $request->query('employment_type', ''));
        $this->applyPublicNameFilter($query, 'experience', 'classifiedExperienceRange', (string) $request->query('experience', ''));

        $company = trim((string) $request->query('company', ''));
        if ($company !== '') {
            $this->applyCompanyNameFilter($query, $company);
        }

        $this->applyEmployerFilter($query, $request);

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

        $this->applyMasterOrLegacyFilter($query, $request, 'industry_id', 'industry', Industry::class);

        $categoryId = $request->integer('job_category_id');
        if ($categoryId > 0) {
            $query->where('job_category_id', $categoryId);
        }

        $titleId = $request->integer('job_title_id');
        if ($titleId > 0) {
            $query->where('job_title_id', $titleId);
        }

        return $query;
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
            ->with($this->publicJobRelations())
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
     * Public jobs for the candidate dashboard: prefer the candidate's industry when set,
     * exclude roles they already applied to, then fill with newest visible listings.
     *
     * @return list<Job>
     */
    public function recommendedForCandidate(User $user, int $limit = 6): array
    {
        $profile = $user->candidateProfile;
        $profile?->loadMissing(['industry']);

        $appliedIds = $user->applications()
            ->pluck('job_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $base = Job::query()
            ->with($this->publicJobRelations())
            ->publiclyVisible()
            ->when($appliedIds !== [], fn ($query) => $query->whereNotIn('id', $appliedIds));

        $preferred = (clone $base);
        if ($profile?->industry_id) {
            $preferred->where('industry_id', $profile->industry_id);
        } elseif (filled($profile?->industry?->name)) {
            $preferred->where('industry', $profile->industry->name);
        } else {
            $preferred = null;
        }

        $jobs = $preferred instanceof \Illuminate\Database\Eloquent\Builder
            ? $preferred->latest('posted_date')->limit($limit)->get()
            : collect();

        if ($jobs->count() < $limit) {
            $exclude = $jobs->pluck('id')->merge($appliedIds)->unique()->values()->all();
            $fill = (clone $base)
                ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
                ->latest('posted_date')
                ->limit($limit - $jobs->count())
                ->get();
            $jobs = $jobs->merge($fill);
        }

        if ($jobs->isEmpty()) {
            return (clone $base)->latest('posted_date')->limit($limit)->get()->all();
        }

        return $jobs->take($limit)->values()->all();
    }

    /**
     * @return list<Job>
     */
    public function featuredPublic(): array
    {
        return Job::query()
            ->with($this->publicJobRelations())
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
            'locations' => $this->publicDisplayValues($base, fn (Job $job) => $job->displayLocation()),
            'industries' => $this->publicDisplayValues($base, fn (Job $job) => $job->displayIndustry()),
            'categories' => $this->publicDisplayValues($base, fn (Job $job) => $job->displayPublicCategory()),
            'employment_types' => $this->publicDisplayValues($base, fn (Job $job) => $job->displayEmploymentType()),
            'experiences' => $this->publicDisplayValues($base, fn (Job $job) => $job->displayExperience()),
            'companies' => $this->distinctCompanyNames(),
            'salaries' => $this->distinctValues((clone $base), 'salary'),
        ];
    }

    /**
     * Public company label shown on job cards ({@see Job::toPublicArray()}): the employer's public display
     * name, falling back to the job's own `project` text for jobs that are not linked to an employer.
     */
    public static function publicCompanyLabel(Job $job): string
    {
        return trim((string) ($job->employer?->publicDisplayCompany() ?: $job->project));
    }

    /**
     * Label for the Company select when the page is opened with `?employer=` (id, business ID or name),
     * so the visible filter control mirrors what the backend is narrowing by. Null when not resolvable.
     */
    public function employerFilterLabel(Request $request): ?string
    {
        $employer = $this->employerFilterValue($request);
        if ($employer === '') {
            return null;
        }

        $profile = $this->findEmployerProfile($employer);
        if ($profile instanceof EmployerProfile) {
            return trim($profile->publicDisplayCompany()) ?: null;
        }

        return ctype_digit($employer) ? null : $employer;
    }

    private function employerFilterValue(Request $request): string
    {
        $value = trim((string) $request->query('employer', ''));

        return $value !== '' ? $value : trim((string) $request->query('employer_id', ''));
    }

    private function findEmployerProfile(string $value): ?EmployerProfile
    {
        if (ctype_digit($value)) {
            return EmployerProfile::query()->with('user')->find((int) $value);
        }

        return EmployerProfile::query()->with('user')->where('business_id', $value)->first();
    }

    /**
     * `?employer=` / `?employer_id=` accepts an employer profile id, a business ID (ONT-E-…) or a company
     * name. Anything that matches no employer narrows to no results rather than being silently ignored.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Job>  $query
     */
    private function applyEmployerFilter($query, Request $request): void
    {
        $value = $this->employerFilterValue($request);
        if ($value === '') {
            return;
        }

        $profile = $this->findEmployerProfile($value);
        if ($profile instanceof EmployerProfile) {
            $query->where('employer_id', $profile->id);

            return;
        }

        if (ctype_digit($value) || preg_match('/^ONT-E-\d+$/i', $value) === 1) {
            $query->whereRaw('1 = 0');

            return;
        }

        $this->applyCompanyNameFilter($query, $value);
    }

    /**
     * Case-insensitive exact match on the public company label (see {@see self::publicCompanyLabel()}),
     * so every option offered by the Company select actually narrows to its own jobs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Job>  $query
     */
    private function applyCompanyNameFilter($query, string $company): void
    {
        $needle = mb_strtolower(trim($company));

        $query->where(function ($inner) use ($needle) {
            $inner->whereHas('employer', function ($employer) use ($needle) {
                $employer->where(function ($named) use ($needle) {
                    $named->whereRaw('LOWER(TRIM(company_name)) = ?', [$needle])
                        ->orWhere(function ($unnamed) use ($needle) {
                            $unnamed->where(fn ($blank) => $blank->whereNull('company_name')->orWhereRaw("TRIM(company_name) = ''"))
                                ->where(function ($fallback) use ($needle) {
                                    $fallback->whereHas('user', fn ($user) => $user->whereRaw('LOWER(TRIM(name)) = ?', [$needle]))
                                        ->orWhereRaw('LOWER(TRIM(business_id)) = ?', [$needle]);
                                });
                        });
                });
            })->orWhere(function ($linkedProject) use ($needle) {
                $linkedProject->whereNotNull('employer_id')->whereRaw('LOWER(TRIM(project)) = ?', [$needle]);
            })->orWhere(function ($unlinked) use ($needle) {
                $unlinked->whereNull('employer_id')->whereRaw('LOWER(TRIM(project)) = ?', [$needle]);
            });
        });
    }

    /**
     * @return list<string>
     */
    private function distinctCompanyNames(): array
    {
        return Job::query()
            ->publiclyVisible()
            ->with('employer.user')
            ->get()
            ->map(fn (Job $job) => self::publicCompanyLabel($job))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function publicJobRelations(): array
    {
        return [
            'employer.user',
            'classifiedIndustry',
            'classifiedCategory',
            'classifiedLocation',
            'classifiedEmploymentType',
            'classifiedExperienceRange',
        ];
    }

    /**
     * Public jobs board: match denormalized column or active master-data name (FK jobs).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Job>  $query
     */
    private function applyPublicNameFilter($query, string $legacyColumn, string $relation, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $query->where(function ($inner) use ($legacyColumn, $relation, $value) {
            $inner->where($legacyColumn, $value)
                ->orWhereHas($relation, fn ($related) => $related->where('name', $value));
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Job>  $query
     * @return list<string>
     */
    private function publicDisplayValues($query, callable $resolver): array
    {
        return (clone $query)
            ->with([
                'classifiedIndustry',
                'classifiedCategory',
                'classifiedLocation',
                'classifiedEmploymentType',
                'classifiedExperienceRange',
            ])
            ->get()
            ->map(fn (Job $job) => $resolver($job))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
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
