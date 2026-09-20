<?php

namespace App\Support;

use App\Enums\JobPublishingStatus;
use App\Models\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class JobListingQuery
{
    /**
     * @return LengthAwarePaginator<int, Job>
     */
    public function publicIndex(Request $request): LengthAwarePaginator
    {
        $query = Job::query()->publiclyVisible()->latest('posted_date');

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

        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        return $query->paginate(9)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Job>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Job::query()->withCount('applications')->latest('posted_date');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('project', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        $status = (string) $request->query('status', '');
        if ($status === JobPublishingStatus::Published->value || $status === JobPublishingStatus::Draft->value) {
            $query->where('publishing_status', $status);
        }

        foreach (['industry', 'category', 'location'] as $field) {
            $value = trim((string) $request->query($field, ''));
            if ($value !== '') {
                $query->where($field, $value);
            }
        }

        $featured = $request->query('featured');
        if ($featured === '1' || $featured === '0') {
            $query->where('featured', $featured === '1');
        }

        $active = $request->query('active');
        if ($active === '1' || $active === '0') {
            $query->where('active', $active === '1');
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return list<Job>
     */
    public function featuredPublic(): array
    {
        return Job::query()
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
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function adminFilterOptions(): array
    {
        $base = Job::query();

        return [
            'locations' => $this->distinctValues((clone $base), 'location'),
            'industries' => $this->distinctValues((clone $base), 'industry'),
            'categories' => $this->distinctValues((clone $base), 'category'),
        ];
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
