<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResumeImportRequest;
use App\Models\ResumeImportBatch;
use App\Models\User;
use App\Services\ResumeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResumeImportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasPermission('candidates.view'), 403);

        $batches = ResumeImportBatch::query()
            ->with(['items' => fn ($query) => $query->latest('id')->limit(20)])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (ResumeImportBatch $batch) => [
                'id' => $batch->id,
                'created_at' => optional($batch->created_at)?->toDateTimeString(),
                'total_files' => $batch->total_files,
                'successful' => $batch->successful,
                'matched' => $batch->matched,
                'needs_review' => $batch->needs_review,
                'failed' => $batch->failed,
                'emails_queued' => $batch->emails_queued,
                'items' => $batch->items->map(fn ($item) => [
                    'filename' => $item->original_filename,
                    'status' => $item->status,
                    'email_status' => $item->email_status,
                    'message' => $item->message,
                    'ournear_id' => $item->user ? app(\App\Support\OurNearHireIdentity::class)->display($item->user, 'CAN') : null,
                    'profile_url' => $item->user?->username ? route('profiles.show', $item->user) : null,
                ])->values()->all(),
            ]);

        return Inertia::render('Admin/Candidates/ResumeImport', [
            'can_import' => $user->hasPermission('candidates.manage'),
            'batches' => $batches,
        ]);
    }

    public function store(StoreResumeImportRequest $request, ResumeImportService $imports): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $batch = $imports->import($actor, $request->file('files', []));

        return redirect()
            ->route('admin.candidates.imports.index')
            ->with('success', sprintf(
                'Import completed. Created %d, matched %d, needs review %d, failed %d.',
                $batch->successful,
                $batch->matched,
                $batch->needs_review,
                $batch->failed,
            ));
    }
}
