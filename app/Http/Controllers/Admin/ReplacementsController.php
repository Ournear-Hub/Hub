<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelReplacementRequest;
use App\Http\Requests\Admin\IdentifyReplacementCandidateRequest;
use App\Http\Requests\Admin\RequestReplacementRequest;
use App\Models\Application;
use App\Models\Employee;
use App\Models\Replacement;
use App\Services\ReplacementService;
use App\Support\ReplacementQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReplacementsController extends Controller
{
    public function index(Request $request, ReplacementQuery $replacements): Response
    {
        $this->authorize('viewAny', Replacement::class);

        $page = $replacements->adminIndex($request)
            ->through(fn (Replacement $replacement) => $replacement->toAdminListArray());

        $selected = $replacements->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Replacements/Index', [
            'replacements' => $page,
            'selected' => $selected?->toAdminArray(),
            'eligibleApplications' => $selected && $selected->canIdentifyCandidate()
                ? $replacements->eligibleApplications($selected)
                : [],
            'filters' => $replacements->filters($request),
            'options' => $replacements->options(),
            'statuses' => array_map(fn (ReplacementStatus $status) => $status->value, ReplacementStatus::cases()),
            'reasons' => array_map(fn (ReplacementReason $reason) => $reason->value, ReplacementReason::cases()),
        ]);
    }

    public function store(
        RequestReplacementRequest $request,
        Employee $employee,
        ReplacementService $service,
    ): RedirectResponse {
        $replacement = $service->request(
            $employee,
            $request->user(),
            ReplacementReason::from($request->string('reason')->toString()),
            $request->input('notes'),
        );

        return redirect()
            ->route('admin.replacements.index', ['replacement' => $replacement->id])
            ->with('success', 'Replacement requested. Original employee and placement remain unchanged.');
    }

    public function identify(
        IdentifyReplacementCandidateRequest $request,
        Replacement $replacement,
        ReplacementService $service,
    ): RedirectResponse {
        $application = Application::query()->findOrFail($request->integer('application_id'));
        $service->identifyApplication($replacement, $application, $request->user());

        return back()->with('success', 'Replacement candidate identified. Continue through the existing recruitment workflow.');
    }

    public function cancel(
        CancelReplacementRequest $request,
        Replacement $replacement,
        ReplacementService $service,
    ): RedirectResponse {
        $service->cancel($replacement, $request->user(), $request->input('notes'));

        return back()->with('success', 'Replacement cancelled. Original employee remains on file.');
    }
}
