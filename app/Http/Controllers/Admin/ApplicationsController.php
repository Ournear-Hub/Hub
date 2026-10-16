<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Enums\ScreeningAction;
use App\Enums\ScreeningDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordScreeningActionRequest;
use App\Http\Requests\Admin\SaveApplicationInterviewRequest;
use App\Http\Requests\Admin\SaveApplicationScreeningRequest;
use App\Http\Requests\Admin\ShortlistApplicationRequest;
use App\Http\Requests\Admin\StoreEnquiryNoteRequest;
use App\Http\Requests\Admin\SubmitApplicationToEmployerRequest;
use App\Http\Requests\Admin\UpdateApplicationStatusRequest;
use App\Models\Application;
use App\Models\RejectionReason;
use App\Models\ScorecardTemplate;
use App\Models\Task;
use App\Services\ApplicationPipelineService;
use App\Services\ApplicationResumeDeliveryService;
use App\Services\EmployerSubmissionService;
use App\Services\ScreeningWorkspaceService;
use App\Support\ApplicationQuery;
use App\Support\StaffOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationsController extends Controller
{
    public function index(Request $request, ApplicationQuery $applications): Response
    {
        return $this->workspace($request, $applications, 'applications', 'Admin/Applications/Index');
    }

    public function screening(Request $request, ApplicationQuery $applications): Response
    {
        return $this->workspace($request, $applications, 'screening', 'Admin/Screening/Index');
    }

    public function shortlisted(Request $request, ApplicationQuery $applications): Response
    {
        return $this->workspace($request, $applications, 'shortlisted', 'Admin/Shortlisted/Index');
    }

    public function updateStatus(
        UpdateApplicationStatusRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $pipeline->changeStatus(
            $application,
            $request->enum('status', ApplicationStatus::class),
            $request->user(),
        );

        return back()->with('success', 'Application status updated.');
    }

    public function storeNote(
        StoreEnquiryNoteRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $pipeline->addNote(
            $application,
            $request->string('note')->toString(),
            $request->user(),
        );

        return back()->with('success', 'Internal note added.');
    }

    public function saveInterview(
        SaveApplicationInterviewRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $pipeline->saveInterview($application, [
            'scheduled_at' => $request->string('scheduled_at')->toString(),
            'mode' => $request->enum('mode', InterviewMode::class),
            'location_or_link' => $request->input('location_or_link'),
            'interviewer_name' => $request->input('interviewer_name'),
            'interviewer_user_id' => $request->filled('interviewer_user_id') ? $request->integer('interviewer_user_id') : null,
            'outcome' => $request->enum('outcome', InterviewOutcome::class),
            'notes' => $request->input('notes'),
            'type' => $request->filled('type') ? $request->enum('type', InterviewType::class) : null,
        ], $request->user());

        return back()->with('success', 'Interview scheduled.');
    }

    public function saveScreening(
        SaveApplicationScreeningRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $pipeline->saveScreening(
            $application,
            $request->enum('decision', ScreeningDecision::class),
            $request->user(),
            $request->input('notes'),
            $request->filled('rejection_reason_id') ? $request->integer('rejection_reason_id') : null,
        );

        $decision = $request->enum('decision', ScreeningDecision::class);

        return back()->with('success', match ($decision) {
            ScreeningDecision::Shortlisted => 'Candidate shortlisted.',
            ScreeningDecision::NotSuitable => 'Candidate marked not suitable.',
            ScreeningDecision::Suitable => 'Candidate marked suitable.',
            default => 'Screening updated.',
        });
    }

    public function shortlist(
        ShortlistApplicationRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $pipeline->shortlist(
            $application,
            $request->user(),
            $request->input('notes'),
        );

        return back()->with('success', 'Candidate shortlisted.');
    }

    public function recordScreeningAction(
        RecordScreeningActionRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $action = $request->enum('action', ScreeningAction::class);
        $remarks = $request->input('remarks') ?: $request->input('notes');

        $pipeline->recordScreeningAction(
            $application,
            $action,
            $request->user(),
            is_string($remarks) ? $remarks : null,
        );

        return back()->with('success', $action->successMessage());
    }

    public function submitToEmployer(
        SubmitApplicationToEmployerRequest $request,
        Application $application,
        EmployerSubmissionService $submissions,
    ): RedirectResponse {
        $submission = $submissions->submit($application, $request->user());

        return redirect()
            ->route('admin.submissions.index', ['submission' => $submission->id])
            ->with('success', 'Candidate submitted to the employer.');
    }

    public function resume(
        Request $request,
        Application $application,
        ApplicationResumeDeliveryService $resumes,
    ): StreamedResponse {
        $this->authorize('downloadResume', $application);

        $label = $application->business_id ?: 'application-'.$application->id;

        return $resumes->stream($request, $application, $label);
    }

    private function workspace(
        Request $request,
        ApplicationQuery $applications,
        string $view,
        string $component,
    ): Response {
        $this->authorize('viewAny', Application::class);

        $workspace = app(ScreeningWorkspaceService::class);

        $page = $applications->adminIndex($request, $view)
            ->through(fn (Application $application) => $workspace->enrichListItem($application));

        $selected = $applications->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        $selectedPayload = $selected?->toAdminArray(true);
        if ($selected && is_array($selectedPayload)) {
            $selectedPayload = $workspace->enrichSelected($selected, $selectedPayload);
        }

        $jobId = $request->integer('job_id');

        return Inertia::render($component, [
            'applications' => $page,
            'selected' => $selectedPayload,
            'jobSummary' => $view === 'screening' ? $workspace->jobSummary($jobId > 0 ? $jobId : null) : null,
            'filters' => $applications->filters($request),
            'options' => $applications->adminFilterOptions(),
            'statuses' => array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases()),
            'interviewModes' => array_map(fn (InterviewMode $mode) => $mode->value, InterviewMode::cases()),
            'interviewOutcomes' => array_map(fn (InterviewOutcome $outcome) => $outcome->value, InterviewOutcome::cases()),
            'interviewTypes' => InterviewType::options(),
            'staffUsers' => StaffOptions::users(),
            'can_create_task' => $request->user()?->can('create', Task::class) === true,
            'scorecardTemplates' => ScorecardTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (ScorecardTemplate $template) => $template->toAdminArray())
                ->all(),
            'rejectionReasons' => RejectionReason::query()
                ->ordered()
                ->get(['id', 'name', 'is_active'])
                ->map(fn (RejectionReason $reason) => [
                    'id' => $reason->id,
                    'name' => $reason->name,
                    'selectable' => $reason->isSelectable(),
                ])
                ->all(),
        ]);
    }
}
