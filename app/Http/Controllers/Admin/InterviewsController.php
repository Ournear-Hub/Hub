<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelApplicationInterviewRequest;
use App\Http\Requests\Admin\ConfirmApplicationInterviewRequest;
use App\Http\Requests\Admin\RescheduleApplicationInterviewRequest;
use App\Http\Requests\Admin\SaveApplicationInterviewRequest;
use App\Http\Requests\Admin\StoreInterviewScorecardRequest;
use App\Http\Requests\Admin\UpdateApplicationInterviewRequest;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ScorecardTemplate;
use App\Services\InterviewService;
use App\Services\ScorecardService;
use App\Support\InterviewQuery;
use App\Support\StaffOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InterviewsController extends Controller
{
    public function index(Request $request, InterviewQuery $interviews): Response
    {
        $this->authorize('viewAny', ApplicationInterview::class);

        $selected = null;
        if ($request->filled('interview')) {
            $selected = ApplicationInterview::query()
                ->with(['application.job', 'interviewer', 'scorecard.template', 'scorecard.submitter'])
                ->find($request->integer('interview'));
            if ($selected && $request->user()?->cannot('view', $selected)) {
                $selected = null;
            }
        }

        return Inertia::render('Admin/Interviews/Index', [
            'interviews' => $interviews->adminIndex($request)
                ->through(fn (ApplicationInterview $interview) => $interview->toAdminListArray()),
            'selected' => $selected?->toAdminListArray(),
            'filters' => $interviews->filters($request),
            'interviewTypes' => InterviewType::options(),
            'lifecycleStatuses' => InterviewLifecycleStatus::options(),
            'interviewModes' => array_map(fn (InterviewMode $mode) => $mode->value, InterviewMode::cases()),
            'interviewOutcomes' => array_map(fn (InterviewOutcome $outcome) => $outcome->value, InterviewOutcome::cases()),
            'staffUsers' => StaffOptions::users(),
            'scorecardTemplates' => ScorecardTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (ScorecardTemplate $template) => $template->toAdminArray())
                ->all(),
            'can_manage' => $request->user()?->hasPermission(PermissionName::ApplicationsManage->value) === true,
        ]);
    }

    public function store(
        SaveApplicationInterviewRequest $request,
        Application $application,
        InterviewService $interviews,
    ): RedirectResponse {
        $interviews->schedule($application, [
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

    public function update(
        UpdateApplicationInterviewRequest $request,
        ApplicationInterview $interview,
        InterviewService $interviews,
    ): RedirectResponse {
        $interviews->updateDetails($interview, $request->validated(), $request->user());

        return back()->with('success', 'Interview updated.');
    }

    public function confirm(
        ConfirmApplicationInterviewRequest $request,
        ApplicationInterview $interview,
        InterviewService $interviews,
    ): RedirectResponse {
        $interviews->confirm($interview, $request->user());

        return back()->with('success', 'Interview confirmed.');
    }

    public function reschedule(
        RescheduleApplicationInterviewRequest $request,
        ApplicationInterview $interview,
        InterviewService $interviews,
    ): RedirectResponse {
        $interviews->reschedule($interview, [
            'scheduled_at' => $request->string('scheduled_at')->toString(),
            'mode' => $request->enum('mode', InterviewMode::class),
            'location_or_link' => $request->input('location_or_link'),
            'interviewer_name' => $request->input('interviewer_name'),
            'interviewer_user_id' => $request->filled('interviewer_user_id') ? $request->integer('interviewer_user_id') : null,
            'notes' => $request->input('notes'),
            'type' => $request->filled('type') ? $request->enum('type', InterviewType::class) : $interview->type,
        ], $request->user());

        return back()->with('success', 'Interview rescheduled.');
    }

    public function cancel(
        CancelApplicationInterviewRequest $request,
        ApplicationInterview $interview,
        InterviewService $interviews,
    ): RedirectResponse {
        $interviews->cancel($interview, $request->user(), $request->input('cancelled_reason'));

        return back()->with('success', 'Interview cancelled.');
    }

    public function saveScorecard(
        StoreInterviewScorecardRequest $request,
        ApplicationInterview $interview,
        ScorecardService $scorecards,
    ): RedirectResponse {
        $scorecards->submit($interview, $request->validated(), $request->user());

        return back()->with('success', 'Scorecard saved. Scores do not change hiring status.');
    }
}
