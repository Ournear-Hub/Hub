<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HiringCommandCenterRequest;
use App\Models\User;
use App\Services\RecruiterHiringCommandCenterService;
use Inertia\Inertia;
use Inertia\Response;

class HiringCommandCenterController extends Controller
{
    public function index(HiringCommandCenterRequest $request, RecruiterHiringCommandCenterService $hiring): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('Admin/Hiring/CommandCenter', $hiring->payload(
            $user,
            $request->line(),
            $request->jobId(),
        ));
    }
}
