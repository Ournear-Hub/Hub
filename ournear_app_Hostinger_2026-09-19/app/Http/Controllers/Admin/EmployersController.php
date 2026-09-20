<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\Industry;
use App\Models\RecruitmentActivity;
use App\Support\EmployerQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployersController extends Controller
{
    public function index(Request $request, EmployerQuery $employers): Response
    {
        $this->authorize('viewAny', EmployerProfile::class);

        $page = $employers->adminIndex($request)
            ->through(fn (EmployerProfile $employer) => $employer->toAdminArray());

        $selected = $employers->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Employers/Index', [
            'employers' => $page,
            'selected' => $selected?->toAdminArray(),
            'jobs' => $selected ? $this->jobs($selected) : [],
            'submissions' => $selected ? $this->submissions($selected) : [],
            'enquiries' => $selected ? $this->enquiries($selected) : [],
            'activities' => $selected ? $this->activities($selected) : [],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'industry_id' => $request->string('industry_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'employer' => $selected ? (string) $selected->id : '',
            ],
            'industries' => Industry::query()->ordered()->get(['id', 'name'])->map(fn (Industry $industry) => [
                'id' => $industry->id,
                'name' => $industry->name,
            ])->all(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobs(EmployerProfile $employer): array
    {
        return $employer->jobs()
            ->withCount('applications')
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($job) => $job->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function submissions(EmployerProfile $employer): array
    {
        return $employer->submissions()
            ->with(['job', 'application.user.candidateProfile'])
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn ($submission) => $submission->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function enquiries(EmployerProfile $employer): array
    {
        return $employer->enquiries()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($enquiry) => $enquiry->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activities(EmployerProfile $employer): array
    {
        $jobIds = $employer->jobs()->pluck('id');

        return RecruitmentActivity::query()
            ->with('actor:id,name')
            ->where(function ($query) use ($employer, $jobIds) {
                $query->where('employer_profile_id', $employer->id)
                    ->orWhereHas('submission', fn ($submission) => $submission->where('employer_profile_id', $employer->id));

                if ($jobIds->isNotEmpty()) {
                    $query->orWhereHas('application', fn ($application) => $application->whereIn('job_id', $jobIds));
                }
            })
            ->latest()
            ->limit(30)
            ->get()
            ->unique('id')
            ->values()
            ->map(fn (RecruitmentActivity $activity) => [
                'id' => $activity->id,
                'title' => $activity->body ?: ($activity->from_status && $activity->to_status
                    ? $activity->from_status.' → '.$activity->to_status
                    : $activity->type->value),
                'meta' => trim(implode(' · ', array_filter([
                    $activity->actor?->name,
                    $activity->created_at?->toDateTimeString(),
                ]))),
            ])
            ->all();
    }
}
