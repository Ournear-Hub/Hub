<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Support\LoginLogQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginLogsController extends Controller
{
    public function index(Request $request, LoginLogQuery $logs): Response
    {
        $this->authorize('viewAny', LoginLog::class);

        $page = $logs->adminIndex($request)
            ->through(fn (LoginLog $log) => $log->toAdminArray());

        $selected = $logs->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/LoginLogs/Index', [
            'logs' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $logs->filters($request),
            'events' => $logs->events(),
        ]);
    }
}
