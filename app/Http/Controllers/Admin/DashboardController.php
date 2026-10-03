<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        $user = $request->user();
        $actor = $user instanceof User ? $user : null;

        return Inertia::render('Admin/Dashboard', [
            'staff_name' => $actor?->name ?? 'Admin',
            'stats' => $dashboard->stats($actor),
            'recentApplications' => $dashboard->recentApplications($actor),
            'recentEmployerEnquiries' => $dashboard->recentEmployerEnquiries($actor),
        ]);
    }
}
