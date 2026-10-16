<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployerSubmissionStatus;
use App\Enums\RecruiterOperationalFollowUpType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecruiterOperationalFollowUpRequest;
use App\Http\Requests\Admin\StoreTalentPoolSubmissionMessageRequest;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Services\RecruiterOperationalFollowUpService;
use App\Services\TalentPoolCoordinationMessagingService;
use App\Support\EmployerVisibleCandidate;
use App\Support\RecruiterOperationalAccess;
use App\Support\RecruitmentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TalentPoolCompanySubmissionsController extends Controller
{
    public function show(Request $request, TalentPoolCompanySubmission $submission): Response
    {
        $this->authorize('view', $submission);
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $submission->loadMissing([
            'candidateUser.candidateProfile',
            'employerProfile',
            'responsibleRecruiter',
            'submitter',
            'rejectionReason',
            'statusEvents.actor',
        ]);

        $statusTimeline = $submission->statusEvents
            ->sortBy('id')
            ->map(fn ($event) => [
                'key' => 'status-'.$event->id,
                'kind' => 'employer_status',
                'label' => RecruitmentWorkspace::responseLabel(
                    EmployerSubmissionStatus::tryFrom((string) $event->to_status) ?? $submission->status,
                ),
                'actor_name' => $event->actor?->name,
                'notes' => $event->notes,
                'at' => $event->created_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        $followUps = app(RecruiterOperationalFollowUpService::class)->timelineForViewer($viewer, $submission);
        $messages = app(TalentPoolCoordinationMessagingService::class)->threadFor($viewer, $submission);

        $canManage = RecruiterOperationalAccess::canManageSubmissionOperational($viewer, $submission);
        $canViewInternal = RecruiterOperationalAccess::canViewInternalOperational($viewer);

        return Inertia::render('Admin/TalentPoolCompanySubmissions/Show', [
            'submission' => [
                ...$submission->toAdminListArray(),
                'candidate' => EmployerVisibleCandidate::fromTalentPoolSubmission($submission),
                'submitted_at' => $submission->submitted_at?->toDateTimeString(),
                'candidate_owner_recruiter_user_id' => $submission->candidate_owner_recruiter_user_id,
                'company_owner_recruiter_user_id' => $submission->company_owner_recruiter_user_id,
                'rejection_reason' => $submission->rejectionReason?->name,
                'internal_notes' => $canViewInternal ? $submission->internal_notes : null,
                'employer_notes' => $submission->employer_notes,
            ],
            'status_timeline' => $statusTimeline,
            'follow_ups' => $followUps,
            'messages' => $messages,
            'can_manage' => $canManage,
            'can_view_internal' => $canViewInternal,
            'follow_up_type_options' => RecruiterOperationalFollowUpType::options(),
            'visibility_options' => [
                ['value' => RecruitmentOperationalVisibility::Internal->value, 'label' => RecruitmentOperationalVisibility::Internal->label()],
                ['value' => RecruitmentOperationalVisibility::EmployerShared->value, 'label' => RecruitmentOperationalVisibility::EmployerShared->label()],
            ],
            'resume_url' => route('account.coordinated-talent-pool.resume', $submission, false),
        ]);
    }

    public function storeFollowUp(
        StoreRecruiterOperationalFollowUpRequest $request,
        TalentPoolCompanySubmission $submission,
        RecruiterOperationalFollowUpService $service,
    ): RedirectResponse {
        $service->record($request->user(), [
            'follow_up_type' => RecruiterOperationalFollowUpType::from($request->string('follow_up_type')->toString()),
            'occurred_at' => $request->filled('occurred_at') ? Carbon::parse($request->input('occurred_at')) : now(),
            'summary' => $request->string('summary')->toString(),
            'outcome' => $request->input('outcome'),
            'issue' => $request->input('issue'),
            'next_action' => $request->input('next_action'),
            'next_action_due_at' => $request->filled('next_action_due_at')
                ? Carbon::parse($request->input('next_action_due_at'))
                : null,
            'visibility' => RecruitmentOperationalVisibility::from($request->string('visibility')->toString()),
            'talent_pool_company_submission_id' => $submission->id,
        ]);

        return back()->with('success', 'Follow-up recorded.');
    }

    public function storeMessage(
        StoreTalentPoolSubmissionMessageRequest $request,
        TalentPoolCompanySubmission $submission,
        TalentPoolCoordinationMessagingService $messaging,
    ): RedirectResponse {
        $messaging->send($request->user(), $submission, $request->string('body')->toString());

        return back()->with('success', 'Message sent.');
    }
}
