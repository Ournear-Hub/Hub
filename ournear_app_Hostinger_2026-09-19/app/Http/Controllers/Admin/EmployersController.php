<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Industry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\Replacement;
use App\Support\EmployerQuery;
use App\Support\FinanceQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployersController extends Controller
{
    public function postingApproval(Request $request, EmployerProfile $employer): RedirectResponse
    {
        $this->authorize('update', $employer);
        $approved = $employer->job_posting_approved_at === null;

        $employer->forceFill([
            'job_posting_approved_at' => $approved ? now() : null,
            'job_posting_approved_by' => $approved ? $request->user()->id : null,
        ])->save();

        return back()->with('success', $approved
            ? 'Employer job posting approved.'
            : 'Employer job posting approval revoked.');
    }

    public function index(Request $request, EmployerQuery $employers, FinanceQuery $finance): Response
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
            'applications' => $selected ? $this->applications($selected) : [],
            'submissions' => $selected ? $this->submissions($selected) : [],
            'selections' => $selected ? $this->selections($selected) : [],
            'placements' => $selected ? $this->placements($selected) : [],
            'employees' => $selected ? $this->employees($selected) : [],
            'replacements' => $selected ? $this->replacements($selected) : [],
            'enquiries' => $selected ? $this->enquiries($selected) : [],
            'activities' => $selected ? $this->activities($selected) : [],
            'finance' => $selected ? $finance->summary($selected->id) : null,
            'invoices' => $selected ? $this->invoices($selected) : [],
            'payments' => $selected ? $this->payments($selected) : [],
            'filters' => $employers->filters($request),
            'industries' => Industry::query()->ordered()->get(['id', 'name'])->map(fn (Industry $industry) => [
                'id' => $industry->id,
                'name' => $industry->name,
            ])->all(),
            'accountStatuses' => array_map(fn (AccountStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ], AccountStatus::cases()),
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
    private function applications(EmployerProfile $employer): array
    {
        return $employer->applications()
            ->with(['user.candidateProfile', 'job'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'business_id' => $application->business_id,
                'status' => $application->status->value,
                'name' => $application->full_name,
                'candidate_id' => $application->user?->candidateProfile?->id,
                'candidate_business_id' => $application->user?->candidateProfile?->business_id,
                'job' => $application->job ? [
                    'id' => $application->job->id,
                    'business_id' => $application->job->business_id,
                    'title' => $application->job->title,
                    'slug' => $application->job->slug,
                ] : null,
                'workspace_url' => route('admin.applications.index', ['application' => $application->id], false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function submissions(EmployerProfile $employer): array
    {
        return $employer->submissions()
            ->with(['job', 'application.user.candidateProfile', 'placement'])
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (EmployerSubmission $submission) => $submission->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function selections(EmployerProfile $employer): array
    {
        return $employer->selections()
            ->with(['job', 'application.user.candidateProfile', 'placement'])
            ->latest('decided_at')
            ->limit(20)
            ->get()
            ->map(fn (EmployerSubmission $submission) => $submission->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function placements(EmployerProfile $employer): array
    {
        return $employer->placements()
            ->with(['job', 'application.user.candidateProfile', 'submission', 'offer', 'employee'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Placement $placement) => $placement->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function employees(EmployerProfile $employer): array
    {
        return $employer->employees()
            ->with(['job', 'application.user.candidateProfile', 'placement', 'candidateProfile'])
            ->latest('joined_at')
            ->limit(20)
            ->get()
            ->map(fn (Employee $employee) => $employee->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function replacements(EmployerProfile $employer): array
    {
        return $employer->replacements()
            ->with([
                'job',
                'originalEmployee.application',
                'originalPlacement',
                'replacementApplication',
                'replacementEmployee.application',
            ])
            ->latest('requested_at')
            ->limit(20)
            ->get()
            ->map(fn (Replacement $replacement) => $replacement->toAdminListArray())
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
    private function invoices(EmployerProfile $employer): array
    {
        return $employer->invoices()
            ->with(['job', 'employee.application', 'payments'])
            ->latest('billed_at')
            ->limit(20)
            ->get()
            ->map(fn (Invoice $invoice) => $invoice->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payments(EmployerProfile $employer): array
    {
        return $employer->payments()
            ->with(['invoice', 'employer.user', 'recorder:id,name'])
            ->latest('paid_at')
            ->limit(20)
            ->get()
            ->map(fn (Payment $payment) => $payment->toAdminArray())
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
