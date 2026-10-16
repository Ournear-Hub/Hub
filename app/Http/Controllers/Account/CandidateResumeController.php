<?php

namespace App\Http\Controllers\Account;

use App\Enums\DocumentType;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReplaceCandidateResumeRequest;
use App\Http\Requests\Account\StoreCandidateResumeRequest;
use App\Http\Requests\Account\UpdateCandidateResumeRequest;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Services\CandidatePrivateFileService;
use App\Services\CandidateResumeLibraryService;
use App\Services\ResumeBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateResumeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $profile = $this->ownedProfile($request);
        $user = $request->user();

        $documents = $profile->documents()
            ->get()
            ->map->toAccountArray()
            ->values()
            ->all();

        return Inertia::render('Account/Resumes/Index', [
            'resumes' => $profile->resumes()
                ->where('status', FileLifecycleStatus::Active)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->get()
                ->map
                ->toAccountArray()
                ->values()
                ->all(),
            'visibilities' => FileVisibility::options(),
            'completion_items' => $user->profileCompletionItems(),
            'profile_completion' => (int) $user->profile_completion,
            'documents' => $documents,
            'document_types' => DocumentType::options(),
            'document_visibilities' => FileVisibility::options(),
            'public_profile_url' => filled($user->username)
                ? route('profiles.show', ['user' => $user->username], false)
                : null,
            'public_profile_share_enabled' => $profile->shareAllows('enabled'),
        ]);
    }

    public function builder(Request $request, ResumeBuilderService $builder): InertiaResponse
    {
        $this->ownedProfile($request);

        return Inertia::render('Account/Resumes/Builder', [
            'preview' => $builder->preview($request->user()),
            'completion_items' => $request->user()->profileCompletionItems(),
            'profile_completion' => (int) $request->user()->profile_completion,
        ]);
    }

    public function builderDownload(Request $request, ResumeBuilderService $builder): Response
    {
        $this->ownedProfile($request);
        $preview = $builder->preview($request->user());
        $html = view('account.hire-resume-export', ['preview' => $preview])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ournear-hire-resume.html"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(
        StoreCandidateResumeRequest $request,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $library->upload(
            $profile,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return back()->with('success', 'Resume uploaded.');
    }

    public function update(
        UpdateCandidateResumeRequest $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->update($resume, $request->validated(), $request->user());

        return back()->with('success', 'Resume updated.');
    }

    public function replace(
        ReplaceCandidateResumeRequest $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->replace($resume, $request->file('file'), $request->user());

        return back()->with('success', 'Resume replaced.');
    }

    public function primary(
        Request $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->makePrimary($resume, $request->user());

        return back()->with('success', 'Primary resume updated.');
    }

    public function archive(
        Request $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedResume($request, $resume, 'update');
        $library->archive($resume, $request->user());

        return back()->with('success', 'Resume archived.');
    }

    public function destroy(
        Request $request,
        CandidateResume $resume,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedResume($request, $resume, 'delete');
        $library->delete($resume, $request->user());

        return back()->with('success', 'Resume deleted.');
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

    private function authorizeOwnedResume(Request $request, CandidateResume $resume, string $ability): CandidateProfile
    {
        $profile = $this->ownedProfile($request);
        $this->authorize($ability, $resume);
        abort_unless((int) $resume->candidate_profile_id === (int) $profile->id, 403);

        return $profile;
    }

    private function ownedProfile(Request $request): CandidateProfile
    {
        $user = $request->user();
        abort_unless($user?->isCandidate() === true, 403);

        $profile = $user->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);
        abort_unless((int) $profile->user_id === (int) $user->id, 403);

        return $profile;
    }
}
