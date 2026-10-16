<?php

namespace App\Services;

use App\Enums\EmployerNotificationEvent;
use App\Models\EmployerProfile;
use App\Models\User;
use App\Notifications\EmployerLifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EmployerStaffingAccessNotificationService
{
    public function accessRequested(EmployerProfile $profile, User $companyUser): void
    {
        app(AdminAlertService::class)->staffingAccessRequested(
            (string) ($profile->company_name ?? 'A company'),
            (int) $profile->id,
            ['user_id' => $companyUser->id],
        );
    }

    public function accessApproved(EmployerProfile $profile): void
    {
        $this->notifyCompanyUser(
            $profile,
            EmployerNotificationEvent::StaffingAccessApproved,
            'Staffing access approved',
            'Your company can now submit staffing requirements and use approved Staffing workspace features.',
            route('account.enquiries.index', [], false),
            ['employer_profile_id' => $profile->id, 'decision' => 'approved'],
        );
    }

    public function accessRejected(EmployerProfile $profile, string $reason): void
    {
        $body = 'Your Staffing access request was not approved.';
        if (trim($reason) !== '') {
            $body .= ' Reason: '.trim($reason);
        }

        $this->notifyCompanyUser(
            $profile,
            EmployerNotificationEvent::StaffingAccessRejected,
            'Staffing access not approved',
            $body,
            route('account.enquiries.index', [], false),
            ['employer_profile_id' => $profile->id, 'decision' => 'rejected'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function notifyCompanyUser(
        EmployerProfile $profile,
        EmployerNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $recipient = $profile->user;
        if (! $recipient instanceof User) {
            return;
        }

        $fingerprint = $event->value.':employer_profile_id='.$profile->id;

        DB::afterCommit(function () use ($recipient, $event, $title, $body, $url, $context, $fingerprint) {
            try {
                $fresh = $recipient->fresh();
                if (! $fresh instanceof User) {
                    return;
                }

                Notification::send($fresh, new EmployerLifecycleNotification(
                    event: $event,
                    title: $title,
                    body: $body,
                    url: $url,
                    fingerprint: $fingerprint,
                    context: $context,
                ));
            } catch (\Throwable $throwable) {
                Log::warning('Employer staffing access notification failed', [
                    'event' => $event->value,
                    'user_id' => $recipient->id,
                    'message' => $throwable->getMessage(),
                ]);
            }
        });
    }
}
