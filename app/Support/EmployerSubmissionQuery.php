<?php

namespace App\Support;

use App\Enums\EmployerSubmissionStatus;
use App\Models\CandidateProfile;
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
                'placement',
                'submitter:id,name',
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

        $candidateId = $request->integer('candidate_id');
        if ($candidateId > 0) {
            $query->whereHas('application.user.candidateProfile', fn (Builder $profile) => $profile->whereKey($candidateId));
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($request->string('scope')->toString() === 'pending' && $status === '') {
            $this->constrainPendingResponse($query);
        }

        $response = trim((string) $request->query('response_status', ''));
        if ($response !== '') {
            $mapped = $this->responseStatusToStored($response);
            if ($mapped === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('status', $mapped);
            }
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
                'placement',
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
            'candidate_id' => $request->string('candidate_id')->toString(),
            'status' => $request->string('status')->toString(),
            'response_status' => $request->string('response_status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'scope' => $request->string('scope')->toString(),
            'submission' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * Employer submissions awaiting a staff-visible employer response.
     *
     * @return Builder<EmployerSubmission>
     */
    public function pendingResponseQuery(): Builder
    {
        $query = EmployerSubmission::query()
            ->with([
                'employer.user',
                'job',
                'application.user.candidateProfile',
                'submitter:id,name',
            ])
            ->latest('submitted_at');

        $this->constrainPendingResponse($query);

        return $query;
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
            'candidates' => CandidateProfile::query()
                ->with('user')
                ->whereHas('user.applications.submissions')
                ->orderBy('id')
                ->get()
                ->map(fn (CandidateProfile $profile) => [
                    'id' => $profile->id,
                    'business_id' => $profile->business_id,
                    'name' => $profile->user?->name,
                    'label' => trim(implode(' · ', array_filter([
                        $profile->business_id,
                        $profile->user?->name,
                    ]))),
                ])
                ->all(),
            'statuses' => array_map(fn (EmployerSubmissionStatus $status) => $status->value, EmployerSubmissionStatus::cases()),
            'response_statuses' => [
                ['value' => EmployerSubmissionStatus::Submitted->value, 'label' => 'Awaiting employer response'],
                ['value' => EmployerSubmissionStatus::UnderReview->value, 'label' => 'Under Review'],
                ['value' => EmployerSubmissionStatus::Selected->value, 'label' => 'Selected'],
                ['value' => EmployerSubmissionStatus::Rejected->value, 'label' => 'Rejected'],
                ['value' => EmployerSubmissionStatus::OnHold->value, 'label' => 'On Hold'],
                ['value' => 'Interview Requested', 'label' => 'Interview Requested (Not available in V1.1)'],
            ],
        ];
    }

    /**
     * @param  Builder<EmployerSubmission>  $query
     */
    public function constrainPendingResponse(Builder $query): void
    {
        $query->whereIn('status', [
            EmployerSubmissionStatus::Submitted->value,
            EmployerSubmissionStatus::UnderReview->value,
        ]);
    }

    private function responseStatusToStored(string $response): ?string
    {
        return match ($response) {
            EmployerSubmissionStatus::Submitted->value,
            'Pending',
            'pending',
            'awaiting' => EmployerSubmissionStatus::Submitted->value,
            EmployerSubmissionStatus::UnderReview->value,
            'under_review' => EmployerSubmissionStatus::UnderReview->value,
            EmployerSubmissionStatus::Selected->value,
            'selected' => EmployerSubmissionStatus::Selected->value,
            EmployerSubmissionStatus::Rejected->value,
            'rejected' => EmployerSubmissionStatus::Rejected->value,
            EmployerSubmissionStatus::OnHold->value,
            'on_hold' => EmployerSubmissionStatus::OnHold->value,
            default => null,
        };
    }
}
