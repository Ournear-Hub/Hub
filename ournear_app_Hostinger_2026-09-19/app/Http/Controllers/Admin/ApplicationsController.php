<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveApplicationInterviewRequest;
use App\Http\Requests\Admin\StoreEnquiryNoteRequest;
use App\Http\Requests\Admin\UpdateApplicationStatusRequest;
use App\Models\Application;
use App\Services\ApplicationPipelineService;
use App\Support\ApplicationQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationsController extends Controller
{
    public function index(Request $request, ApplicationQuery $applications): Response
    {
        $this->authorize('viewAny', Application::class);

        $page = $applications->adminIndex($request)->through(fn (Application $application) => $application->toAdminListArray());

        $selected = null;
        $selectedId = $request->integer('application');
        if ($selectedId > 0) {
            $selected = Application::query()->with(['job', 'interview', 'activities.actor'])->find($selectedId);
        }

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $page,
            'selected' => $selected?->toAdminArray(true),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'job_id' => $request->string('job_id')->toString(),
                'industry' => $request->string('industry')->toString(),
                'location' => $request->string('location')->toString(),
                'experience' => $request->string('experience')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'application' => $selectedId > 0 ? (string) $selectedId : '',
            ],
            'options' => $applications->adminFilterOptions(),
            'statuses' => array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases()),
            'interviewModes' => array_map(fn (InterviewMode $mode) => $mode->value, InterviewMode::cases()),
            'interviewOutcomes' => array_map(fn (InterviewOutcome $outcome) => $outcome->value, InterviewOutcome::cases()),
        ]);
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
            'interviewer_name' => $request->string('interviewer_name')->toString(),
            'outcome' => $request->enum('outcome', InterviewOutcome::class),
            'notes' => $request->input('notes'),
        ], $request->user());

        return back()->with('success', 'Interview saved.');
    }

    public function resume(Application $application): StreamedResponse
    {
        $this->authorize('downloadResume', $application);

        $path = $application->getAttributes()['resume_path'] ?? null;

        if (! is_string($path) || $path === '' || ! Storage::disk('resumes')->exists($path)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $downloadName = 'application-'.$application->id.'.'.$extension;

        return Storage::disk('resumes')->download($path, $downloadName);
    }
}
