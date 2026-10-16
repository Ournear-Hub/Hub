<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InternalCandidateResumeSource;
use App\Http\Controllers\Controller;
use App\Models\InternalCandidateResume;
use App\Services\InternalCandidateResumeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalCandidateResumeController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', InternalCandidateResume::class);

        $search = trim((string) $request->query('q', ''));

        $records = InternalCandidateResume::query()
            ->with(['creator:id,name', 'user:id,name,username'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('display_name', 'like', $like)
                        ->orWhere('original_filename', 'like', $like);
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (InternalCandidateResume $record) => [
                'id' => $record->id,
                'display_name' => $record->display_name,
                'source' => $record->source->value,
                'source_label' => $record->source->label(),
                'original_filename' => $record->original_filename,
                'file_size' => $record->file_size,
                'linked_user' => $record->user ? [
                    'name' => $record->user->name,
                    'username' => $record->user->username,
                ] : null,
                'created_at' => $record->created_at?->toDateTimeString(),
                'download_url' => route('admin.candidates.internal-resumes.download', $record, false),
            ]);

        return Inertia::render('Admin/Candidates/InternalResumes/Index', [
            'records' => $records,
            'filters' => ['q' => $search],
            'sources' => collect(InternalCandidateResumeSource::cases())
                ->map(fn (InternalCandidateResumeSource $case) => [
                    'value' => $case->value,
                    'label' => str_replace('_', ' ', ucfirst($case->value)),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request, InternalCandidateResumeService $service): RedirectResponse
    {
        Gate::authorize('create', InternalCandidateResume::class);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:180'],
            'source' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
        ]);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $service->store($user, $request->file('file'), [
            'display_name' => $validated['display_name'],
            'source' => $validated['source'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Internal resume stored.');
    }

    public function download(Request $request, InternalCandidateResume $internalResume, InternalCandidateResumeService $service): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return $service->download($user, $internalResume);
    }
}
