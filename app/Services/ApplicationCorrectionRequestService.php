<?php

namespace App\Services;

use App\Enums\ApplicationCorrectionRequestStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\ApplicationCorrectionRequest;
use App\Models\User;
use App\Support\EmployerApplicationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationCorrectionRequestService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private EmployerNotificationService $employerNotifications,
    ) {}

    public function submit(Application $application, User $candidate, string $body): ApplicationCorrectionRequest
    {
        if (! $candidate->isCandidate() || (int) $application->user_id !== (int) $candidate->id) {
            throw ValidationException::withMessages([
                'body' => 'You cannot submit a correction request for this application.',
            ]);
        }

        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Describe what needs to be corrected or clarified.',
            ]);
        }

        if ($application->status->isTerminal()) {
            throw ValidationException::withMessages([
                'body' => 'Correction requests are not available for closed applications.',
            ]);
        }

        $hasOpen = $application->correctionRequests()
            ->whereIn('status', [
                ApplicationCorrectionRequestStatus::Pending->value,
                ApplicationCorrectionRequestStatus::Acknowledged->value,
            ])
            ->exists();

        if ($hasOpen) {
            throw ValidationException::withMessages([
                'body' => 'You already have an open correction request for this application.',
            ]);
        }

        return DB::transaction(function () use ($application, $candidate, $body) {
            $request = ApplicationCorrectionRequest::query()->create([
                'application_id' => $application->id,
                'candidate_user_id' => $candidate->id,
                'body' => $body,
                'status' => ApplicationCorrectionRequestStatus::Pending,
            ]);

            $this->activities->record(
                actor: $candidate,
                type: RecruitmentActivityType::CorrectionRequestSubmitted,
                application: $application,
                body: 'Candidate submitted a correction or clarification request.',
            );

            $this->employerNotifications->notifyCorrectionRequestSubmitted($application, $request);

            return $request;
        });
    }

    public function updateStatus(
        ApplicationCorrectionRequest $request,
        User $handler,
        ApplicationCorrectionRequestStatus $status,
        ?string $responseNote = null,
    ): ApplicationCorrectionRequest {
        $application = $request->application;
        $canEmployer = $handler->isEmployer()
            && EmployerApplicationAccess::managesApplication($handler, $application);
        $canAdmin = $handler->isAdmin()
            && $handler->can('update', $application);

        if (! $canEmployer && ! $canAdmin) {
            throw ValidationException::withMessages([
                'status' => 'You cannot update this correction request.',
            ]);
        }

        if ($request->status === ApplicationCorrectionRequestStatus::Resolved) {
            throw ValidationException::withMessages([
                'status' => 'This correction request is already resolved.',
            ]);
        }

        return DB::transaction(function () use ($request, $handler, $status, $responseNote, $application) {
            $request->update([
                'status' => $status,
                'handled_by_user_id' => $handler->id,
                'handled_at' => now(),
                'response_note' => $responseNote,
            ]);

            $this->activities->record(
                actor: $handler,
                type: RecruitmentActivityType::CorrectionRequestUpdated,
                application: $application,
                body: 'Correction request marked '.$status->label().'.',
            );

            return $request->fresh() ?? $request;
        });
    }
}
