<?php

namespace App\Http\Controllers\Account;

use App\Enums\EmployerSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCoordinatedTalentPoolMessageRequest;
use App\Http\Requests\Account\UpdateCoordinatedTalentPoolSubmissionRequest;
use App\Services\RecruiterOperationalFollowUpService;
use App\Services\TalentPoolCoordinationMessagingService;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\EmployerProfile;
use App\Models\RejectionReason;
use App\Models\TalentPoolCompanySubmission;
use App\Services\CandidatePrivateFileService;
use App\Services\TalentPoolCompanySubmissionService;
use App\Support\EmployerVisibleCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerCoordinatedTalentPoolController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $this->profile($request);
        $this->authorize('viewAny', TalentPoolCompanySubmission::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(EmployerSubmissionStatus::cases(), 'value'))],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;

        $query = TalentPoolCompanySubmission::query()
            ->where('employer_profile_id', $profile->id)
            ->with(['candidateUser.candidateProfile']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->whereHas('candidateUser', fn ($user) => $user->where('name', 'like', $like));
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $submissions = $query
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (TalentPoolCompanySubmission $submission) => EmployerVisibleCandidate::fromTalentPoolSubmission($submission));

        return Inertia::render('Account/Employer/CoordinatedTalentPool/Index', [
            'company' => [
                'name' => $profile->displayCompany(),
                'business_id' => $profile->business_id,
            ],
            'submissions' => $submissions,
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
            ],
            'status_options' => collect(EmployerSubmissionStatus::cases())
                ->map(fn (EmployerSubmissionStatus $case) => [
                    'value' => $case->value,
                    'label' => \App\Support\RecruitmentWorkspace::responseLabel($case),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, TalentPoolCompanySubmission $submission): Response
    {
        $profile = $this->profile($request);
        $this->authorize('view', $submission);
        abort_unless((int) $submission->employer_profile_id === (int) $profile->id, 404);

        $submission->loadMissing(['candidateUser.candidateProfile', 'statusEvents.actor', 'rejectionReason']);

        $candidate = EmployerVisibleCandidate::fromTalentPoolSubmission($submission);
        $timeline = $submission->statusEvents
            ->map(fn ($event) => [
                'key' => (string) $event->id,
                'label' => trim(($event->to_status ?? 'Update').($event->actor?->name ? ' · '.$event->actor->name : '')),
                'at' => $event->created_at?->toDateTimeString(),
                'notes' => $event->notes,
            ])
            ->values()
            ->all();

        $sharedFollowUps = app(RecruiterOperationalFollowUpService::class)->timelineForViewer($request->user(), $submission);
        $messages = app(TalentPoolCoordinationMessagingService::class)->threadFor($request->user(), $submission);

        return Inertia::render('Account/Employer/CoordinatedTalentPool/Show', [
            'company' => [
                'name' => $profile->displayCompany(),
                'business_id' => $profile->business_id,
            ],
            'submission' => [
                ...$candidate,
                'timeline' => $timeline,
                'rejection_reason' => $submission->rejectionReason?->name,
            ],
            'shared_follow_ups' => $sharedFollowUps,
            'messages' => $messages,
            'rejection_reasons' => RejectionReason::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'list_filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', '')),
            ],
        ]);
    }

    public function storeMessage(
        StoreCoordinatedTalentPoolMessageRequest $request,
        TalentPoolCompanySubmission $submission,
        TalentPoolCoordinationMessagingService $messaging,
    ): RedirectResponse {
        $messaging->send($request->user(), $submission, $request->string('body')->toString());

        return back()->with('success', 'Message sent.');
    }

    public function updateStatus(
        UpdateCoordinatedTalentPoolSubmissionRequest $request,
        TalentPoolCompanySubmission $submission,
        TalentPoolCompanySubmissionService $service,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $action = $request->string('action')->toString();

        match ($action) {
            'review' => $service->review($submission, $user, $request->input('notes')),
            'hold' => $service->hold($submission, $user, $request->input('notes')),
            'select' => $service->select($submission, $user, $request->input('notes')),
            'reject' => $service->reject(
                $submission,
                $user,
                $request->filled('rejection_reason_id') ? $request->integer('rejection_reason_id') : null,
                $request->input('notes'),
            ),
            default => abort(422),
        };

        return back()->with('success', 'Submission status updated.');
    }

    public function resume(
        Request $request,
        TalentPoolCompanySubmission $submission,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $this->profile($request);
        $this->authorize('downloadResume', $submission);
        abort_unless((int) $submission->employer_profile_id === (int) $request->user()?->employerProfile?->id, 404);

        $profile = $submission->candidateUser?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);

        $resume = CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();

        abort_unless($resume instanceof CandidateResume, 404);

        return $files->download(
            $resume->storage_path,
            $resume->original_filename ?: 'resume.'.$resume->extension,
            $request->string('disposition')->toString() === 'inline',
            $resume->storage_disk,
        );
    }

    private function profile(Request $request): EmployerProfile
    {
        $profile = $request->user()?->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);

        return $profile;
    }
}
