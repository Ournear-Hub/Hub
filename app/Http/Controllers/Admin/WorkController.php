<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CrmLead;
use App\Models\EmployerProfile;
use App\Models\Campaign;
use App\Models\Investor;
use App\Models\Task;
use App\Services\MyWorkService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    public function index(Request $request, MyWorkService $work): Response
    {
        $user = $request->user();
        abort_unless(
            $user?->can('viewAny', Application::class)
                || $user?->can('viewAny', Task::class)
                || $user?->can('viewAny', EmployerProfile::class)
                || $user?->can('viewAny', CrmLead::class)
                || $user?->can('viewAny', Investor::class)
                || $user?->can('viewAny', Campaign::class),
            403
        );

        return Inertia::render('Admin/Work/Index', $work->payload($user));
    }
}
