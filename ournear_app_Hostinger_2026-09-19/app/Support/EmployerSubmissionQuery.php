<?php

namespace App\Support;

use App\Enums\EmployerSubmissionStatus;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EmployerSubmissionQuery
{
    /**
     * @return LengthAwarePaginator<int, EmployerSubmission>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = EmployerSubmission::query()
            ->with([
                'employer.user',
                'job',
                'application.user.candidateProfile',
            ])
            ->latest('submitted_at');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('business_id', 'like', '%'.$search.'%')
                    ->orWhereHas('employer', function (Builder $employer) use ($search) {
                        $employer->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('job', function (Builder $job) use ($search) {
                        $job->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('title', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('application', function (Builder $application) use ($search) {
                        $application->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('full_name', 'like', '%'.$search.'%')
                            ->orWhereHas('user.candidateProfile', fn (Builder $profile) => $profile->where('business_id', 'like', '%'.$search.'%'));
                    });
            });
        }

        $employerId = $request->integer('employer_id');
        if ($employerId > 0) {
            $query->where('employer_profile_id', $employerId);
        }

        $jobId = $request->integer('job_id');
        if ($jobId > 0) {
            $query->where('job_id', $jobId);
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('submitted_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('submitted_at', '<=', $to);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?EmployerSubmission
    {
        $id = $request->integer('submission');
        if ($id <= 0) {
            return null;
        }

        return EmployerSubmission::query()
            ->with([
                'employer.user',
                'employer.classifiedIndustry',
                'job',
                'application.job',
                'application.user.candidateProfile',
                'application.screening',
                'application.interview',
                'application.activities.actor:id,name',
                'rejectionReason',
                'submitter:id,name',
                'decider:id,name',
                'activities.actor:id,name',
            ])
            ->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('submission');

        return [
            'search' => $request->string('search')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'submission' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'employers' => EmployerProfile::query()
                ->with('user')
                ->orderBy('company_name')
                ->get()
                ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                ->all(),
            'jobs' => Job::query()->orderBy('title')->get(['id', 'title', 'business_id'])->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'business_id' => $job->business_id,
            ])->all(),
            'statuses' => array_map(fn (EmployerSubmissionStatus $status) => $status->value, EmployerSubmissionStatus::cases()),
        ];
    }
}
