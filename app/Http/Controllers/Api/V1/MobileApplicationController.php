<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\Api\V1\MobileCandidatePresenter;
use App\Services\ApplicationResumeDeliveryService;
use App\Services\CandidateApplicationViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileApplicationController extends Controller
{
    public function index(Request $request, CandidateApplicationViewService $applications, MobileCandidatePresenter $presenter): JsonResponse
    {
        $paginator = $applications->paginate($request->user(), $request);

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (Application $application) => $presenter->applicationListItem($application))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => $applications->indexSummary($request->user()),
        ]);
    }

    public function show(
        Request $request,
        Application $application,
        CandidateApplicationViewService $applications,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $this->authorizeCandidateApplication($request, $application);

        return response()->json([
            'data' => $presenter->applicationDetail($application),
        ]);
    }

    public function resume(
        Request $request,
        Application $application,
        ApplicationResumeDeliveryService $resumes,
    ): StreamedResponse {
        $this->authorizeCandidateApplication($request, $application);
        $this->authorize('downloadResume', $application);

        return $resumes->stream($request, $application, 'application-'.$application->id);
    }

    private function authorizeCandidateApplication(Request $request, Application $application): void
    {
        $this->authorize('view', $application);

        $user = $request->user();
        abort_unless(
            $user instanceof User && $user->isCandidate() && (int) $application->user_id === (int) $user->id,
            403
        );
    }
}
