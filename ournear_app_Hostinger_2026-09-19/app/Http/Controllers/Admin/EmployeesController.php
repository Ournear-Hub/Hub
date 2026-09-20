<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReplacementReason;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Support\EmployeeQuery;
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
        ]);
    }
}
