<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\SavedJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedJobController extends Controller
{
    public function index(Request $request, SavedJobService $savedJobs): Response
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['all', 'open', 'closed'], true)) {
            $status = 'all';
        }

        return Inertia::render('Account/SavedJobs', [
            'savedJobs' => $savedJobs->paginate($request->user(), $status),
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request, Job $job, SavedJobService $savedJobs): RedirectResponse
    {
        $savedJobs->save($request->user(), $job);

        return back();
    }

    public function destroy(Request $request, Job $job, SavedJobService $savedJobs): RedirectResponse
    {
        $savedJobs->unsave($request->user(), $job);

        return back();
    }
}
