<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\ReplacementReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HideRelationshipReviewRequest;
use App\Models\Employee;
use App\Models\RelationshipReview;
use App\Services\RelationshipReviewService;
use App\Support\EmployeeQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeesController extends Controller
{
    public function index(Request $request, EmployeeQuery $employees): Response
    {
        $this->authorize('viewAny', Employee::class);

        $page = $employees->adminIndex($request)
            ->through(fn (Employee $employee) => $employee->toAdminListArray());

        $selected = $employees->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $employees->filters($request),
            'options' => $employees->options(),
            'replacementReasons' => array_map(fn (ReplacementReason $reason) => $reason->value, ReplacementReason::cases()),
            'canHideReviews' => $request->user()?->hasPermission(PermissionName::EmployeesManage->value) === true,
        ]);
    }

    public function hideReview(
        HideRelationshipReviewRequest $request,
        Employee $employee,
        RelationshipReview $review,
        RelationshipReviewService $reviews,
    ): RedirectResponse {
        abort_unless((int) $review->employee_id === (int) $employee->id, 404);

        $reviews->hide($request->user(), $review);

        return back()->with('success', 'Review hidden.');
    }
}
