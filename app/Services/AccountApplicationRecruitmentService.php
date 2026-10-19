<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewMode;
use App\Models\Application;
use App\Models\RejectionReason;
use App\Models\User;
use App\Support\EmployerApplicationAccess;

class AccountApplicationRecruitmentService
{
    public function __construct(
        private RecruitmentApplicationMessagingService $messaging,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function employerWorkflow(Application $application, User $employer): array
    {
        $application->loadMissing(['interviews', 'correctionRequests', 'placements']);

        $placement = $application->placements->sortByDesc('id')->first();
        $canManage = EmployerApplicationAccess::managesApplication($employer, $application);

        return [
            'can_manage' => $canManage,
            'can_shortlist' => $application->canShortlist(),
            'can_reject' => $application->canMarkNotSuitable(),
            'can_schedule_interview' => ! $application->status->isTerminal(),
            'can_select' => $canManage && in_array($application->status, [ApplicationStatus::Shortlisted, ApplicationStatus::Interview], true),
            'can_open_placement' => $canManage
                && $application->status === ApplicationStatus::Selected
                && $placement === null,
            'can_offer' => $canManage && $placement !== null && $placement->canOffer(),
            'can_accept_offer' => $canManage && $placement !== null && $placement->canAccept(),
            'can_confirm_joining' => $canManage && $placement !== null && $placement->canConfirmJoining(),
            'can_record_joining' => $canManage && $placement !== null && $placement->canJoin(),
            'placement_status_label' => $placement?->status->value,
            'rejection_reasons' => RejectionReason::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (RejectionReason $reason) => [
                    'id' => $reason->id,
                    'name' => $reason->name,
                ])
                ->values()
                ->all(),
            'interview_modes' => array_map(
                fn (InterviewMode $mode) => ['value' => $mode->value, 'label' => ucfirst(str_replace('_', ' ', $mode->value))],
                InterviewMode::cases(),
            ),
            'interviews' => $application->interviews
                ->map(fn ($interview) => $interview->toAccountArray())
                ->values()
                ->all(),
            'messages' => $this->messaging->threadFor($employer, $application),
            'correction_requests' => $application->correctionRequests
                ->map(fn ($request) => $request->toAccountArray())
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function candidateWorkflow(Application $application, User $candidate): array
    {
        $application->loadMissing(['interviews', 'correctionRequests']);

        $openInterview = $application->interviews
            ->first(fn ($interview) => $interview->candidateCanRespond());

        return [
            'interviews' => $application->interviews
                ->map(fn ($interview) => $interview->toAccountArray())
                ->values()
                ->all(),
            'respondable_interview_id' => $openInterview?->id,
            'messages' => $this->messaging->threadFor($candidate, $application),
            'correction_requests' => $application->correctionRequests
                ->map(fn ($request) => $request->toAccountArray())
                ->values()
                ->all(),
            'can_submit_correction_request' => ! $application->status->isTerminal()
                && ! $application->correctionRequests->contains(
                    fn ($request) => in_array($request->status->value, ['pending', 'acknowledged'], true),
                ),
        ];
    }
}
