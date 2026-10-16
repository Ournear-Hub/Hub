<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListPublicJobsRequest;
use App\Models\Job;
use App\Models\User;
use App\Services\SavedJobService;
use App\Support\JobListingQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicJobController extends Controller
{
    public function filters(JobListingQuery $jobs): JsonResponse
    {
        return response()->json([
            'data' => $jobs->publicFilterOptions(),
        ]);
    }

    public function index(ListPublicJobsRequest $request, JobListingQuery $jobs, SavedJobService $savedJobs): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $paginator = $jobs->publicApiIndex($request, $perPage);
        $viewer = $this->savedJobViewer($request);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (Job $job) => $this->publicJobPayload($job, $viewer, $savedJobs))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, Job $job, SavedJobService $savedJobs): JsonResponse
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $job->loadMissing('employer.user');
        $viewer = $this->savedJobViewer($request);

        return response()->json([
            'data' => $this->publicJobPayload($job, $viewer, $savedJobs, detail: true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicJobPayload(Job $job, ?User $viewer, SavedJobService $savedJobs, bool $detail = false): array
    {
        $payload = $detail ? $job->toPublicDetail() : $job->toPublicCard();

        if ($viewer instanceof User) {
            $payload['is_saved'] = $savedJobs->isSaved($viewer, $job);
        }

        return $payload;
    }

    private function savedJobViewer(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User && $user->isCandidate() ? $user : null;
    }
}
