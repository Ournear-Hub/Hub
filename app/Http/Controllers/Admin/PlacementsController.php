<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlacementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmPlacementJoiningRequest;
use App\Http\Requests\Admin\CreatePlacementRequest;
use App\Http\Requests\Admin\DecidePlacementRequest;
use App\Http\Requests\Admin\RecordPlacementJoinedRequest;
use App\Http\Requests\Admin\RecordPlacementOfferRequest;
use App\Models\EmployerSubmission;
use App\Models\Placement;
use App\Services\PlacementService;
use App\Support\PlacementQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlacementsController extends Controller
{
    public function index(Request $request, PlacementQuery $placements): Response
    {
        $this->authorize('viewAny', Placement::class);

        $page = $placements->adminIndex($request)
            ->through(fn (Placement $placement) => $placement->toAdminListArray());

        $selected = $placements->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Placements/Index', [
            'placements' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $placements->filters($request),
            'options' => $placements->options(),
            'statuses' => array_map(fn (PlacementStatus $status) => $status->value, PlacementStatus::cases()),
        ]);
    }

    public function store(
        CreatePlacementRequest $request,
        EmployerSubmission $submission,
        PlacementService $service,
    ): RedirectResponse {
        $placement = $service->createFromSubmission($submission, $request->user(), $request->input('notes'));

        return redirect()
            ->route('admin.placements.index', ['placement' => $placement->id])
            ->with('success', 'Placement created from the selected submission.');
    }

    public function offer(
        RecordPlacementOfferRequest $request,
        Placement $placement,
        PlacementService $service,
    ): RedirectResponse {
        $service->offer(
            $placement,
            $request->user(),
            $request->string('offered_at')->toString(),
            $request->input('salary'),
            $request->input('notes'),
        );

        return back()->with('success', 'Offer recorded.');
    }

    public function accept(
        DecidePlacementRequest $request,
        Placement $placement,
        PlacementService $service,
    ): RedirectResponse {
        $service->accept($placement, $request->user(), $request->input('notes'));

        return back()->with('success', 'Offer accepted.');
    }

    public function decline(
        DecidePlacementRequest $request,
        Placement $placement,
        PlacementService $service,
    ): RedirectResponse {
        $service->decline($placement, $request->user(), $request->input('notes'));

        return back()->with('success', 'Offer declined.');
    }

    public function confirmJoining(
        ConfirmPlacementJoiningRequest $request,
        Placement $placement,
        PlacementService $service,
    ): RedirectResponse {
        $service->confirmJoining(
            $placement,
            $request->user(),
            $request->string('expected_joining_date')->toString(),
            $request->input('notes'),
        );

        return back()->with('success', 'Joining confirmed.');
    }

    public function join(
        RecordPlacementJoinedRequest $request,
        Placement $placement,
        PlacementService $service,
    ): RedirectResponse {
        $placement = $service->join(
            $placement,
            $request->user(),
            $request->string('actual_joining_date')->toString(),
            $request->input('notes'),
        );

        $employeeId = $placement->employee?->id;

        return redirect()
            ->route('admin.employees.index', $employeeId ? ['employee' => $employeeId] : [])
            ->with('success', 'Candidate joined. Employee record created.');
    }

    public function withdraw(
        DecidePlacementRequest $request,
        Placement $placement,
        PlacementService $service,
    ): RedirectResponse {
        $service->withdraw($placement, $request->user(), $request->input('notes'));

        return back()->with('success', 'Placement withdrawn.');
    }
}
