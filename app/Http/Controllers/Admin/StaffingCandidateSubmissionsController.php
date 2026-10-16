<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecruiterOperationalFollowUpType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffingOperationalFollowUpRequest;
use App\Http\Requests\StoreStaffingSubmissionMessageRequest;
use App\Models\StaffingCandidateSubmission;
use App\Models\User;
use App\Services\StaffingCoordinationMessagingService;
use App\Services\StaffingOperationalFollowUpService;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffingCandidateSubmissionsController extends Controller
{
    public function show(Request $request, StaffingCandidateSubmission $staffingSubmission): Response
    {
        $this->authorize('view', $staffingSubmission);
        $viewer = $request->user();
        abort_unless($viewer instanceof User, 403);

        $staffingSubmission->load([
            'candidateUser.candidateProfile',
            'employerEnquiry.user.employerProfile',
            'responsibleRecruiter',
            'statusEvents.actor',
        ]);

        $canViewInternal = RecruiterOperationalAccess::canViewInternalOperational($viewer);
        $followUps = app(StaffingOperationalFollowUpService::class)->timelineForViewer($viewer, $staffingSubmission);
        $messages = app(StaffingCoordinationMessagingService::class)->threadFor($viewer, $staffingSubmission);

        return Inertia::render('Admin/StaffingCandidateSubmissions/Show', [
            'submission' => [
                ...$staffingSubmission->toAdminListArray(),
                'internal_notes' => $canViewInternal ? $staffingSubmission->internal_notes : null,
                'client_feedback' => $staffingSubmission->client_feedback,
                'joining_date' => $staffingSubmission->joining_date?->toDateString(),
                'joining_confirmation_notes' => $staffingSubmission->joining_confirmation_notes,
                'status_events' => $staffingSubmission->statusEvents
                    ->map(fn ($event) => [
                        'from_status' => $event->from_status,
                        'to_status' => $event->to_status,
                        'notes' => $event->notes,
                        'actor_name' => $event->actor?->name,
                        'created_at' => $event->created_at?->toDateTimeString(),
                    ])
                    ->all(),
            ],
            'enquiry' => $staffingSubmission->employerEnquiry?->toAdminArray(),
            'follow_ups' => $followUps,
            'messages' => $messages,
            'can_manage' => RecruiterOperationalAccess::canManageStaffingSubmissionOperational($viewer, $staffingSubmission),
            'can_message' => RecruiterOperationalAccess::canMessageOnStaffingSubmission($viewer, $staffingSubmission),
            'can_view_internal' => $canViewInternal,
            'follow_up_type_options' => RecruiterOperationalFollowUpType::options(),
            'visibility_options' => [
                ['value' => RecruitmentOperationalVisibility::Internal->value, 'label' => RecruitmentOperationalVisibility::Internal->label()],
                ['value' => RecruitmentOperationalVisibility::EmployerShared->value, 'label' => RecruitmentOperationalVisibility::EmployerShared->label()],
            ],
        ]);
    }

    public function storeFollowUp(
        StoreStaffingOperationalFollowUpRequest $request,
        StaffingCandidateSubmission $staffingSubmission,
        StaffingOperationalFollowUpService $service,
    ): RedirectResponse {
        $service->record($request->user(), $staffingSubmission, [
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
        ]);

        return back()->with('success', 'Follow-up recorded.');
    }

    public function storeMessage(
        StoreStaffingSubmissionMessageRequest $request,
        StaffingCandidateSubmission $staffingSubmission,
        StaffingCoordinationMessagingService $messaging,
    ): RedirectResponse {
        $messaging->send($request->user(), $staffingSubmission, $request->string('body')->toString());

        return back()->with('success', 'Message sent.');
    }
}
