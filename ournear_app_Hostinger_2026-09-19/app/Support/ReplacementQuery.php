<?php

namespace App\Support;

use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\Replacement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReplacementQuery
{
    /**
     * @return LengthAwarePaginator<int, Replacement>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Replacement::query()
            ->with([
                'employer.user',
                'job',
                'originalEmployee.application',
                'replacementEmployee.application',
                'replacementApplication.user.candidateProfile',
            ])
            ->latest('requested_at');

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
                    ->orWhereHas('originalEmployee', fn (Builder $employee) => $employee->where('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('originalEmployee.application', function (Builder $application) use ($search) {
                        $application->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('full_name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('replacementApplication', function (Builder $application) use ($search) {
                        $application->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('full_name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('replacementEmployee', fn (Builder $employee) => $employee->where('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('originalPlacement', fn (Builder $placement) => $placement->where('business_id', 'like', '%'.$search.'%'));
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

        $reason = trim((string) $request->query('reason', ''));
        if ($reason !== '') {
            $query->where('reason', $reason);
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('requested_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('requested_at', '<=', $to);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?Replacement
    {
        $id = $request->integer('replacement');
        if ($id <= 0) {
            return null;
        }

        return Replacement::query()
            ->with([
                'employer.user',
                'job',
                'originalEmployee.application.user.candidateProfile',
                'originalPlacement',
                'originalApplication',
                'originalSubmission',
                'replacementApplication.user.candidateProfile',
                'replacementApplication.screening',
                'replacementSubmission',
                'replacementPlacement.employee',
                'replacementEmployee.application',
                'requester:id,name',
                'activities.actor:id,name',
            ])
            ->find($id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function eligibleApplications(Replacement $replacement): array
    {
        return Application::query()
            ->with(['user.candidateProfile', 'submissions', 'screening'])
            ->where('job_id', $replacement->job_id)
            ->whereKeyNot($replacement->original_application_id)
            ->latest()
            ->get()
            ->filter(function (Application $application) use ($replacement) {
                $hasSubmission = $application->submissions->contains(
                    fn ($item) => (int) $item->job_id === (int) $replacement->job_id
                        && (int) $item->employer_profile_id === (int) $replacement->employer_profile_id
                );

                return $hasSubmission || $application->isEligibleForEmployerSubmission();
            })
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'business_id' => $application->business_id,
                'name' => $application->full_name,
                'status' => $application->status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('replacement');

        return [
            'search' => $request->string('search')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'status' => $request->string('status')->toString(),
            'reason' => $request->string('reason')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'replacement' => $selectedId > 0 ? (string) $selectedId : '',
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
            'statuses' => array_map(fn (ReplacementStatus $status) => $status->value, ReplacementStatus::cases()),
            'reasons' => array_map(fn (ReplacementReason $reason) => $reason->value, ReplacementReason::cases()),
        ];
    }
}
