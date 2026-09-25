<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReplaceApplicationResumeRequest;
use App\Http\Requests\Account\WithdrawApplicationRequest;
use App\Models\Application;
use App\Services\ApplicationPipelineService;
use App\Services\ApplicationSubmissionService;
use App\Services\CandidateApplicationViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'filters' => [
                'filter' => trim((string) $request->query('filter', 'all')) ?: 'all',
            ],
        ]);
    }

    public function show(Request $request, Application $application, CandidateApplicationViewService $applications): Response
    {
        $this->authorize('view', $application);

        abort_unless(
            $request->user()?->isCandidate() && (int) $application->user_id === (int) $request->user()->id,
            403
        );

        return Inertia::render('Account/Applications/Show', [
            'application' => $applications->detail($application),
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

    public function resume(Request $request, Application $application): StreamedResponse
    {
        $this->authorize('downloadResume', $application);

        $path = $application->getAttributes()['resume_path'] ?? null;

        if (! is_string($path) || $path === '' || ! Storage::disk('resumes')->exists($path)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $downloadName = 'application-'.$application->id.'.'.$extension;

        return Storage::disk('resumes')->download($path, $downloadName, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
