<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\SavedJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class MobileSavedJobController extends Controller
{
    public function index(Request $request, SavedJobService $savedJobs): JsonResponse
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['all', 'open', 'closed'], true)) {
            $status = 'all';
        }

        $paginator = $savedJobs->paginate($request->user(), $status);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request, Job $job, SavedJobService $savedJobs): JsonResponse
    {
        $savedJobs->save($request->user(), $job);

        return response()->json([
            'message' => 'Job saved.',
            'data' => [
                'job_id' => $job->id,
                'job_slug' => $job->slug,
                'is_saved' => true,
            ],
        ], 201);
    }

    public function destroy(Request $request, Job $job, SavedJobService $savedJobs): JsonResponse
    {
        $savedJobs->unsave($request->user(), $job);

        return response()->json([
            'message' => 'Job unsaved.',
            'data' => [
                'job_id' => $job->id,
                'job_slug' => $job->slug,
                'is_saved' => false,
            ],
        ]);
    }
}
