<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployerSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideEmployerSubmissionRequest;
use App\Http\Requests\Admin\RejectEmployerSubmissionRequest;
use App\Models\EmployerSubmission;
use App\Models\RejectionReason;
use App\Services\EmployerSubmissionService;
use App\Support\EmployerSubmissionQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerSubmissionsController extends Controller
{
    public function index(Request $request, EmployerSubmissionQuery $submissions): Response
    {
        $this->authorize('viewAny', EmployerSubmission::class);

        $page = $submissions->adminIndex($request)
            ->through(fn (EmployerSubmission $submission) => $submission->toAdminListArray());

        $selected = $submissions->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Submissions/Index', [
            'submissions' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $submissions->filters($request),
            'options' => $submissions->options(),
            'statuses' => array_map(fn (EmployerSubmissionStatus $status) => $status->value, EmployerSubmissionStatus::cases()),
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

    public function select(
        DecideEmployerSubmissionRequest $request,
        EmployerSubmission $submission,
        EmployerSubmissionService $service,
    ): RedirectResponse {
        $service->select($submission, $request->user(), $request->input('notes'));

        return back()->with('success', 'Employer selected this candidate for the job.');
    }

    public function reject(
        RejectEmployerSubmissionRequest $request,
        EmployerSubmission $submission,
        EmployerSubmissionService $service,
    ): RedirectResponse {
        $service->reject(
            $submission,
            $request->user(),
            $request->filled('rejection_reason_id') ? $request->integer('rejection_reason_id') : null,
            $request->input('notes'),
        );

        return back()->with('success', 'Employer rejected this candidate for the job.');
    }

    public function hold(
        DecideEmployerSubmissionRequest $request,
        EmployerSubmission $submission,
        EmployerSubmissionService $service,
    ): RedirectResponse {
        $service->hold($submission, $request->user(), $request->input('notes'));

        return back()->with('success', 'Submission put on hold.');
    }

    public function review(
        DecideEmployerSubmissionRequest $request,
        EmployerSubmission $submission,
        EmployerSubmissionService $service,
    ): RedirectResponse {
        $service->review($submission, $request->user(), $request->input('notes'));

        return back()->with('success', 'Submission marked as under review.');
    }
}
