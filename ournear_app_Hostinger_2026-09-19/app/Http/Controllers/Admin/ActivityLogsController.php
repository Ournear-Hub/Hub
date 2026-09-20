<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityActivity;
use App\Support\SecurityActivityQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogsController extends Controller
{
    public function index(Request $request, SecurityActivityQuery $activities): Response
    {
        $this->authorize('viewAny', SecurityActivity::class);

        $page = $activities->adminIndex($request)
            ->through(fn (SecurityActivity $activity) => $activity->toAdminArray());

        $selected = $activities->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/ActivityLogs/Index', [
            'activities' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $activities->filters($request),
            'actions' => $activities->actions(),
        ]);
    }
}
