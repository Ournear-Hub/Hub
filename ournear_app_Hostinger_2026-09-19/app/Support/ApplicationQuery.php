<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ApplicationQuery
{
    /**
     * @return LengthAwarePaginator<int, Application>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Application::query()->with('job')->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%')
                    ->orWhereHas('job', function ($job) use ($search) {
                        $job->where('title', 'like', '%'.$search.'%');
                    });
            });
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $jobId = $request->query('job_id');
        if (filled($jobId)) {
            $query->where('job_id', (int) $jobId);
        }

        $industry = trim((string) $request->query('industry', ''));
        if ($industry !== '') {
            $query->whereHas('job', fn ($job) => $job->where('industry', $industry));
        }

        $location = trim((string) $request->query('location', ''));
        if ($location !== '') {
            $query->where('current_location', $location);
        }

        $experience = trim((string) $request->query('experience', ''));
        if ($experience !== '') {
            $query->where('experience', $experience);
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function adminFilterOptions(): array
    {
        return [
            'jobs' => Job::query()->orderBy('title')->get(['id', 'title', 'slug'])->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
            ])->all(),
            'industries' => Job::query()->whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry')->all(),
            'locations' => Application::query()->whereNotNull('current_location')->distinct()->orderBy('current_location')->pluck('current_location')->all(),
            'experiences' => Application::query()->whereNotNull('experience')->distinct()->orderBy('experience')->pluck('experience')->all(),
        ];
    }
}
