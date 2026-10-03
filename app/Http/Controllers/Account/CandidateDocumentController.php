<?php

namespace App\Http\Controllers\Account;

use App\Enums\DocumentType;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReplaceCandidateDocumentRequest;
use App\Http\Requests\Account\StoreCandidateDocumentRequest;
use App\Http\Requests\Account\UpdateCandidateDocumentRequest;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Services\CandidateDocumentLibraryService;
use App\Services\CandidatePrivateFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $this->ownedProfile($request);
        $type = DocumentType::tryFrom((string) $request->query('type', ''));

        $documents = $profile->documents()
            ->when($type, fn ($query) => $query->where('document_type', $type->value))
            ->get()
            ->map->toAccountArray()
            ->values()
            ->all();

        return Inertia::render('Account/Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'type' => $type?->value,
            ],
            'types' => DocumentType::options(),
            'visibilities' => FileVisibility::options(),
            'statuses' => FileLifecycleStatus::options(),
        ]);
    }

    public function store(
        StoreCandidateDocumentRequest $request,
        CandidateDocumentLibraryService $library,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $library->upload(
            $profile,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return back()->with('success', 'Document uploaded.');
    }

    public function update(
        UpdateCandidateDocumentRequest $request,
        CandidateDocument $document,
        CandidateDocumentLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedDocument($request, $document, 'update');
        $library->update($document, $request->validated(), $request->user());

        return back()->with('success', 'Document updated.');
    }

    public function replace(
        ReplaceCandidateDocumentRequest $request,
        CandidateDocument $document,
        CandidateDocumentLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedDocument($request, $document, 'update');
        $library->replace($document, $request->file('file'), $request->user());

        return back()->with('success', 'Document replaced.');
    }

    public function destroy(
        Request $request,
        CandidateDocument $document,
        CandidateDocumentLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedDocument($request, $document, 'delete');
        $library->delete($document, $request->user());

        return back()->with('success', 'Document deleted.');
    }

    public function download(
        Request $request,
        CandidateDocument $document,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $this->authorizeOwnedDocument($request, $document, 'download');

        return $files->download(
            $document->storage_path,
            $document->original_filename ?: 'document.'.$document->extension,
            $request->string('disposition')->toString() === 'inline',
            $document->storage_disk,
        );
    }

    public function view(
        Request $request,
        CandidateDocument $document,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $request->merge(['disposition' => 'inline']);

        return $this->download($request, $document, $files);
    }

    private function authorizeOwnedDocument(Request $request, CandidateDocument $document, string $ability): CandidateProfile
    {
        $profile = $this->ownedProfile($request);
        $this->authorize($ability, $document);
        abort_unless((int) $document->candidate_profile_id === (int) $profile->id, 403);

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
