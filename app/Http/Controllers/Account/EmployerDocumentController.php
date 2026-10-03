<?php

namespace App\Http\Controllers\Account;

use App\Enums\EmployerDocumentType;
use App\Enums\FileLifecycleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ReplaceEmployerDocumentRequest;
use App\Http\Requests\Account\StoreEmployerDocumentRequest;
use App\Http\Requests\Account\UpdateEmployerDocumentRequest;
use App\Models\EmployerDocument;
use App\Models\EmployerProfile;
use App\Services\EmployerDocumentLibraryService;
use App\Services\EmployerPrivateFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerDocumentController extends Controller
{
    public function index(Request $request, EmployerDocumentLibraryService $library): Response
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('view', $profile);
        $type = EmployerDocumentType::tryFrom((string) $request->query('type', ''));

        $documents = collect($library->list($profile, $type))
            ->map(fn (EmployerDocument $document) => $document->toAccountArray())
            ->values()
            ->all();

        return Inertia::render('Account/Employer/Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'type' => $type?->value,
            ],
            'types' => EmployerDocumentType::options(),
            'statuses' => FileLifecycleStatus::options(),
        ]);
    }

    public function store(
        StoreEmployerDocumentRequest $request,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('create', [EmployerDocument::class, $profile]);

        $library->upload(
            $profile,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return back()->with('success', 'Company document uploaded.');
    }

    public function update(
        UpdateEmployerDocumentRequest $request,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedDocument($request, $document, 'update');
        $library->update($document, $request->validated(), $request->user());

        return back()->with('success', 'Company document updated.');
    }

    public function replace(
        ReplaceEmployerDocumentRequest $request,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedDocument($request, $document, 'update');
        $library->replace($document, $request->file('file'), $request->user());

        return back()->with('success', 'Company document replaced.');
    }

    public function destroy(
        Request $request,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $this->authorizeOwnedDocument($request, $document, 'delete');
        $library->delete($document, $request->user());

        return back()->with('success', 'Company document deleted.');
    }

    public function download(
        Request $request,
        EmployerDocument $document,
        EmployerPrivateFileService $files,
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
        EmployerDocument $document,
        EmployerPrivateFileService $files,
    ): StreamedResponse {
        $request->merge(['disposition' => 'inline']);

        return $this->download($request, $document, $files);
    }

    private function authorizeOwnedDocument(Request $request, EmployerDocument $document, string $ability): EmployerProfile
    {
        $profile = $this->ownedProfile($request);
        $this->authorize($ability, $document);
        abort_unless((int) $document->employer_profile_id === (int) $profile->id, 403);

        return $profile;
    }

    private function ownedProfile(Request $request): EmployerProfile
    {
        $user = $request->user();
        abort_unless($user?->canAccessHiringWorkspace() === true, 403);

        $profile = $user->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 404);
        abort_unless((int) $profile->user_id === (int) $user->id, 403);

        return $profile;
    }
}
