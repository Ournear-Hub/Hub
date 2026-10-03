<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsPeriodRequest;
use App\Models\User;
use App\Services\Admin\OperationalAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(AnalyticsPeriodRequest $request, OperationalAnalyticsService $analytics): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->canViewOperationalAnalytics(), 403);

        return Inertia::render('Admin/Analytics/Index', $analytics->payload(
            $user,
            $request->safe()->only(['from', 'to']),
        ));
    }
}
