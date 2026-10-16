<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FileLifecycleStatus;
use App\Http\Controllers\Api\V1\Concerns\ResolvesCandidateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReplaceCandidateResumeRequest;
use App\Http\Requests\Account\StoreCandidateResumeRequest;
use App\Http\Requests\Account\UpdateCandidateResumeRequest;
use App\Models\CandidateResume;
use App\Services\Api\V1\MobileCandidatePresenter;
use App\Services\CandidatePrivateFileService;
use App\Services\CandidateResumeLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileCandidateResumeController extends Controller
{
    use ResolvesCandidateProfile;

    public function index(Request $request, MobileCandidatePresenter $presenter): JsonResponse
    {
        $profile = $this->ownedProfile($request);

        $resumes = $profile->resumes()
            ->where('status', FileLifecycleStatus::Active)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CandidateResume $resume) => $presenter->resume($resume))
            ->values()
            ->all();

        return response()->json(['data' => $resumes]);
    }

    public function builder(Request $request, MobileCandidatePresenter $presenter): JsonResponse
    {
        $this->ownedProfile($request);

        return response()->json([
            'data' => $presenter->resumeBuilder($request->user()),
        ]);
    }

    public function store(
        StoreCandidateResumeRequest $request,
        CandidateResumeLibraryService $library,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $resume = $library->upload(
            $profile,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'message' => 'Resume uploaded.',
            'data' => $presenter->resume($resume),
        ], 201);
    }

    public function update(
        UpdateCandidateResumeRequest $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->update($resume, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Resume updated.',
            'data' => $presenter->resume($resume->fresh()),
        ]);
    }

    public function replace(
        ReplaceCandidateResumeRequest $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->replace($resume, $request->file('file'), $request->user());

        return response()->json([
            'message' => 'Resume replaced.',
            'data' => $presenter->resume($resume->fresh()),
        ]);
    }

    public function makePrimary(
        Request $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->makePrimary($resume, $request->user());

        return response()->json([
            'message' => 'Primary resume updated.',
            'data' => $presenter->resume($resume->fresh()),
        ]);
    }

    public function destroy(
        Request $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
    ): JsonResponse {
        $this->authorizeOwnedResume($request, $resume, 'delete');
        $library->delete($resume, $request->user());

        return response()->json(['message' => 'Resume deleted.']);
    }

    public function download(
        Request $request,
        CandidateResume $resume,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $this->authorizeOwnedResume($request, $resume, 'download');

        return $files->download(
            $resume->storage_path,
            $resume->original_filename ?: 'resume.'.$resume->extension,
            $request->string('disposition')->toString() === 'inline',
            $resume->storage_disk,
        );
    }

    public function view(
        Request $request,
        CandidateResume $resume,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $request->merge(['disposition' => 'inline']);

        return $this->download($request, $resume, $files);
    }

    private function authorizeOwnedResume(Request $request, CandidateResume $resume, string $ability): void
    {
        $profile = $this->ownedProfile($request);
        $this->authorize($ability, $resume);
        abort_unless((int) $resume->candidate_profile_id === (int) $profile->id, 403);
    }
}
