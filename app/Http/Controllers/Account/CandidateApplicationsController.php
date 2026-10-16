<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReplaceApplicationResumeRequest;
use App\Http\Requests\Account\WithdrawApplicationRequest;
use App\Models\Application;
use App\Services\ApplicationPipelineService;
use App\Services\ApplicationSubmissionService;
use App\Http\Requests\Account\CandidateInterviewResponseRequest;
use App\Http\Requests\Account\CandidateSendApplicationMessageRequest;
use App\Http\Requests\Account\StoreApplicationCorrectionRequest;
use App\Models\ApplicationInterview;
use App\Services\AccountApplicationRecruitmentService;
use App\Services\ApplicationCorrectionRequestService;
use App\Services\CandidateApplicationViewService;
use App\Services\InterviewService;
use App\Services\RecruitmentApplicationMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\ApplicationResumeDeliveryService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateApplicationsController extends Controller
{
    public function index(Request $request, CandidateApplicationViewService $applications): Response
    {
        $page = $applications->paginate($request->user(), $request)->through(
            fn (Application $application) => $applications->listItem($application)
        );

        return Inertia::render('Account/Applications', [
            'applications' => $page,
            'summary' => $applications->indexSummary($request->user()),
            'filters' => [
                'filter' => trim((string) $request->query('filter', 'all')) ?: 'all',
            ],
        ]);
    }

    public function show(
        Request $request,
        Application $application,
        CandidateApplicationViewService $applications,
        AccountApplicationRecruitmentService $recruitment,
    ): Response {
        $this->authorize('view', $application);

        abort_unless(
            $request->user()?->isCandidate() && (int) $application->user_id === (int) $request->user()->id,
            403
        );

        return Inertia::render('Account/Applications/Show', [
            'application' => $applications->detail($application),
            'recruitment' => $recruitment->candidateWorkflow($application, $request->user()),
        ]);
    }

    public function withdraw(
        WithdrawApplicationRequest $request,
        Application $application,
        ApplicationPipelineService $pipeline,
    ): RedirectResponse {
        $pipeline->withdrawByCandidate($application, $request->user());

        return back()->with('success', 'Application withdrawn.');
    }

    public function replaceResume(
        ReplaceApplicationResumeRequest $request,
        Application $application,
        ApplicationSubmissionService $submissions,
    ): RedirectResponse {
        $submissions->replaceResume(
            $application,
            $request->file('resume'),
            $request->user(),
        );

        return back()->with('success', 'Resume updated.');
    }

    public function respondInterview(
        CandidateInterviewResponseRequest $request,
        Application $application,
        ApplicationInterview $interview,
        InterviewService $interviews,
    ): RedirectResponse {
        abort_unless((int) $interview->application_id === (int) $application->id, 404);

        $interviews->recordCandidateResponse(
            $interview,
            $request->user(),
            $request->enum('response', \App\Enums\CandidateInterviewResponse::class),
            $request->input('note'),
        );

        return back()->with('success', 'Interview response recorded.');
    }

    public function storeCorrectionRequest(
        StoreApplicationCorrectionRequest $request,
        Application $application,
        ApplicationCorrectionRequestService $corrections,
    ): RedirectResponse {
        $corrections->submit($application, $request->user(), $request->string('body')->toString());

        return back()->with('success', 'Correction request submitted.');
    }

    public function sendMessage(
        CandidateSendApplicationMessageRequest $request,
        Application $application,
        RecruitmentApplicationMessagingService $messaging,
    ): RedirectResponse {
        $messaging->send($request->user(), $application, $request->string('body')->toString());

        return back()->with('success', 'Message sent.');
    }

    public function resume(
        Request $request,
        Application $application,
        ApplicationResumeDeliveryService $resumes,
    ): StreamedResponse {
        $this->authorize('downloadResume', $application);

        return $resumes->stream($request, $application, 'application-'.$application->id);
    }
}
