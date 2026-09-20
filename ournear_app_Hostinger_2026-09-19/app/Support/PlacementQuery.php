<?php

namespace App\Support;

use App\Enums\PlacementStatus;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\Placement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PlacementQuery
{
    /**
     * @return LengthAwarePaginator<int, Placement>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Placement::query()
            ->with([
                'employer.user',
                'job',
                'application.user.candidateProfile',
                'submission',
                'offer',
                'employee',
                'invoice.payments',
                'originatingReplacements.replacementEmployee.application',
                'resultingReplacement.originalEmployee.application',
                'resultingReplacement.originalPlacement',
            ])
            ->latest();

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
                    })
                    ->orWhereHas('submission', fn (Builder $submission) => $submission->where('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('employee', fn (Builder $employee) => $employee->where('business_id', 'like', '%'.$search.'%'));
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
            $query->whereDate('created_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?Placement
    {
        $id = $request->integer('placement');
        if ($id <= 0) {
            return null;
        }

        return Placement::query()
            ->with([
                'employer.user',
                'employer.classifiedIndustry',
                'job',
                'application.job',
                'application.user.candidateProfile',
                'application.screening',
                'application.interview',
                'application.activities.actor:id,name',
                'submission.activities.actor:id,name',
                'offer.creator:id,name',
                'employee',
                'creator:id,name',
                'joiningConfirmer:id,name',
                'activities.actor:id,name',
                'invoice.payments',
                'originatingReplacements.replacementEmployee.application',
                'resultingReplacement.originalEmployee.application',
                'resultingReplacement.originalPlacement',
            ])
            ->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('placement');

        return [
            'search' => $request->string('search')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'placement' => $selectedId > 0 ? (string) $selectedId : '',
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
            'statuses' => array_map(fn (PlacementStatus $status) => $status->value, PlacementStatus::cases()),
        ];
    }
}
