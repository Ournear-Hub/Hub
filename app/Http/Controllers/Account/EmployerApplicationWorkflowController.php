<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\EmployerRejectApplicationRequest;
use App\Http\Requests\Account\EmployerScheduleApplicationInterviewRequest;
use App\Http\Requests\Account\EmployerSendApplicationMessageRequest;
use App\Http\Requests\Account\EmployerShortlistApplicationRequest;
use App\Http\Requests\Account\EmployerUpdateCorrectionRequestRequest;
use App\Models\Application;
use App\Models\ApplicationCorrectionRequest;
use App\Models\Job;
use App\Services\ApplicationCorrectionRequestService;
use App\Services\EmployerApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;

class EmployerApplicationWorkflowController extends Controller
{
    public function shortlist(
        EmployerShortlistApplicationRequest $request,
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        abort_unless((int) $application->job_id === (int) $job->id, 404);

        $workflow->shortlist($application, $request->user(), $request->input('notes'));

        return back()->with('success', 'Candidate shortlisted.');
    }

    public function reject(
        EmployerRejectApplicationRequest $request,
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        abort_unless((int) $application->job_id === (int) $job->id, 404);

        $workflow->reject(
            $application,
            $request->user(),
            $request->integer('rejection_reason_id'),
            $request->input('notes'),
        );

        return back()->with('success', 'Candidate marked not suitable.');
    }

    public function scheduleInterview(
        EmployerScheduleApplicationInterviewRequest $request,
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        abort_unless((int) $application->job_id === (int) $job->id, 404);

        $workflow->scheduleInterview($application, $request->user(), [
            'scheduled_at' => $request->string('scheduled_at')->toString(),
            'mode' => $request->enum('mode', \App\Enums\InterviewMode::class),
            'location_or_link' => $request->input('location_or_link'),
            'interviewer_name' => $request->input('interviewer_name'),
            'notes' => $request->input('notes'),
        ]);

        return back()->with('success', 'Interview scheduled.');
    }

    public function sendMessage(
        EmployerSendApplicationMessageRequest $request,
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        abort_unless((int) $application->job_id === (int) $job->id, 404);

        $workflow->sendMessage($application, $request->user(), $request->string('body')->toString());

        return back()->with('success', 'Message sent.');
    }

    public function updateCorrectionRequest(
        EmployerUpdateCorrectionRequestRequest $request,
        Job $job,
        Application $application,
        ApplicationCorrectionRequest $correctionRequest,
        ApplicationCorrectionRequestService $corrections,
    ): RedirectResponse {
        abort_unless((int) $application->job_id === (int) $job->id, 404);
        abort_unless((int) $correctionRequest->application_id === (int) $application->id, 404);

        $corrections->updateStatus(
            $correctionRequest,
            $request->user(),
            $request->enum('status', \App\Enums\ApplicationCorrectionRequestStatus::class),
            $request->input('response_note'),
        );

        return back()->with('success', 'Correction request updated.');
    }

    public function select(
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorizeOwnedApplication($job, $application);
        $workflow->select($application, request()->user());

        return back()->with('success', 'Candidate selected for offer.');
    }

    public function openPlacement(
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorizeOwnedApplication($job, $application);
        $workflow->openPlacement($application, request()->user());

        return back()->with('success', 'Placement opened.');
    }

    public function offer(
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorizeOwnedApplication($job, $application);
        $validated = request()->validate([
            'offered_at' => ['required', 'date'],
            'salary' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $workflow->offer(
            $application,
            request()->user(),
            $validated['offered_at'],
            $validated['salary'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Offer recorded.');
    }

    public function acceptOffer(
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorizeOwnedApplication($job, $application);
        $workflow->acceptOffer($application, request()->user(), request()->input('notes'));

        return back()->with('success', 'Offer accepted.');
    }

    public function confirmJoining(
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorizeOwnedApplication($job, $application);
        $validated = request()->validate([
            'expected_joining_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $workflow->confirmJoining(
            $application,
            request()->user(),
            $validated['expected_joining_date'],
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Joining confirmed.');
    }

    public function recordJoining(
        Job $job,
        Application $application,
        EmployerApplicationWorkflowService $workflow,
    ): RedirectResponse {
        $this->authorizeOwnedApplication($job, $application);
        $validated = request()->validate([
            'actual_joining_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $workflow->recordJoining(
            $application,
            request()->user(),
            $validated['actual_joining_date'],
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Candidate marked as joined.');
    }

    private function authorizeOwnedApplication(Job $job, Application $application): void
    {
        abort_unless((int) $application->job_id === (int) $job->id, 404);
        abort_unless(request()->user()?->can('manageAsEmployer', $application) === true, 403);
    }
}
