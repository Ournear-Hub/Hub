<?php

namespace App\Http\Controllers\Account;

use App\Enums\StaffingCandidateSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateStaffingCandidateSubmissionRequest;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\RejectionReason;
use App\Models\StaffingCandidateSubmission;
use App\Services\CandidatePrivateFileService;
use App\Services\StaffingCandidateSubmissionService;
use App\Services\StaffingCoordinationMessagingService;
use App\Services\StaffingOperationalFollowUpService;
use App\Http\Requests\StoreStaffingSubmissionMessageRequest;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerStaffingSubmissionsController extends Controller
{
    public function show(Request $request, StaffingCandidateSubmission $staffingSubmission): Response
    {
        $this->authorize('view', $staffingSubmission);

        $staffingSubmission->load(['statusEvents.actor', 'employerEnquiry']);

        $payload = $staffingSubmission->toClientArray();
        $payload['status_events'] = $staffingSubmission->statusEvents
            ->map(fn ($event) => [
                'to_status' => $event->to_status,
                'notes' => $event->notes,
                'actor_name' => $event->actor?->name,
                'created_at' => $event->created_at?->toDateTimeString(),
            ])
            ->all();

        $user = $request->user();
        abort_unless($user !== null, 403);

        $followUps = app(StaffingOperationalFollowUpService::class)->timelineForViewer($user, $staffingSubmission);
        $messages = app(StaffingCoordinationMessagingService::class)->threadFor($user, $staffingSubmission);

        return Inertia::render('Account/StaffingSubmissions/Show', [
            'submission' => $payload,
            'enquiry' => $staffingSubmission->employerEnquiry?->toAccountArray(),
            'follow_ups' => $followUps,
            'messages' => $messages,
            'can_message' => RecruiterOperationalAccess::canMessageOnStaffingSubmission($user, $staffingSubmission),
            'rejection_reasons' => RejectionReason::query()
                ->where('is_active', true)
                ->orderBy('label')
                ->get(['id', 'label'])
                ->map(fn ($row) => ['id' => $row->id, 'label' => $row->label])
                ->values()
                ->all(),
        ]);
    }

    public function update(
        UpdateStaffingCandidateSubmissionRequest $request,
        StaffingCandidateSubmission $staffingSubmission,
        StaffingCandidateSubmissionService $service,
    ): RedirectResponse {
        $status = $request->enum('status', StaffingCandidateSubmissionStatus::class);
        abort_unless($status instanceof StaffingCandidateSubmissionStatus, 422);

        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($status === StaffingCandidateSubmissionStatus::JoinedConfirmed) {
            $service->confirmJoining(
                $staffingSubmission,
                $user,
                $request->filled('joining_date')
                    ? Carbon::parse($request->input('joining_date'))
                    : now(),
                $request->input('joining_confirmation_notes'),
            );
        } else {
            $service->clientTransition(
                $staffingSubmission,
                $user,
                $status,
                $request->input('client_feedback'),
                $request->filled('rejection_reason_id') ? $request->integer('rejection_reason_id') : null,
            );
        }

        return back()->with('success', 'Staffing candidate submission updated.');
    }

    public function storeMessage(
        StoreStaffingSubmissionMessageRequest $request,
        StaffingCandidateSubmission $staffingSubmission,
        StaffingCoordinationMessagingService $messaging,
    ): RedirectResponse {
        $messaging->send($request->user(), $staffingSubmission, $request->string('body')->toString());

        return back()->with('success', 'Message sent.');
    }

    public function resume(
        Request $request,
        StaffingCandidateSubmission $staffingSubmission,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $this->authorize('view', $staffingSubmission);

        $staffingSubmission->loadMissing('candidateUser.candidateProfile');
        $profile = $staffingSubmission->candidateUser?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);

        $resume = CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();

        abort_unless($resume instanceof CandidateResume, 404);

        $this->authorize('downloadResume', $staffingSubmission);

        return $files->download(
            $resume->storage_path,
            $resume->original_filename ?: 'resume.'.$resume->extension,
            $request->string('disposition')->toString() === 'inline',
            $resume->storage_disk,
        );
    }
}
