<?php

namespace App\Services;

use App\Enums\AdminNotificationEvent;
use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\User;
use App\Notifications\LifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AdminAlertService
{
    public function applicationReceived(Application $application): void
    {
        $application->loadMissing('job');

        $this->send(
            AdminNotificationEvent::ApplicationReceived,
            'Application received',
            trim($application->full_name.' applied for '.($application->job?->title ?? 'a job').'.'),
            '/admin/applications?application='.$application->id,
            ['application_id' => $application->id],
        );
    }

    public function candidateShortlisted(Application $application): void
    {
        $application->loadMissing('job');

        $this->send(
            AdminNotificationEvent::CandidateShortlisted,
            'Candidate shortlisted',
            trim($application->full_name.' was shortlisted for '.($application->job?->title ?? 'a job').'.'),
            '/admin/applications?application='.$application->id,
            ['application_id' => $application->id],
        );
    }

    public function submissionCreated(EmployerSubmission $submission): void
    {
        $submission->loadMissing(['application', 'employer', 'job']);

        $this->send(
            AdminNotificationEvent::SubmissionCreated,
            'Employer submission created',
            trim(($submission->application?->full_name ?? 'A candidate').' was submitted to '.($submission->employer?->displayCompany() ?? 'an employer').'.'),
            '/admin/submissions?submission='.$submission->id,
            ['submission_id' => $submission->id, 'application_id' => $submission->application_id],
        );
    }

    public function employerSelected(EmployerSubmission $submission): void
    {
        $submission->loadMissing(['application', 'job']);

        $this->send(
            AdminNotificationEvent::EmployerSelected,
            'Employer selected a candidate',
            trim(($submission->application?->full_name ?? 'A candidate').' was selected for '.($submission->job?->title ?? 'a job').'.'),
            '/admin/submissions?submission='.$submission->id,
            ['submission_id' => $submission->id],
        );
    }

    public function employerRejected(EmployerSubmission $submission): void
    {
        $submission->loadMissing(['application', 'job']);

        $this->send(
            AdminNotificationEvent::EmployerRejected,
            'Employer rejected a submission',
            trim(($submission->application?->full_name ?? 'A candidate').' was rejected for '.($submission->job?->title ?? 'a job').'.'),
            '/admin/submissions?submission='.$submission->id,
            ['submission_id' => $submission->id],
        );
    }

    public function placementCreated(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::PlacementCreated,
            'Placement created',
            'A placement was created from an employer-selected submission.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function offerCreated(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::OfferCreated,
            'Offer created',
            'An offer was created for a placement.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function offerAccepted(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::OfferAccepted,
            'Offer accepted',
            'An offer was accepted.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function offerDeclined(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::OfferDeclined,
            'Offer declined',
            'An offer was declined.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function joiningConfirmed(Placement $placement): void
    {
        $this->send(
            AdminNotificationEvent::JoiningConfirmed,
            'Joining confirmed',
            'Joining was confirmed for a placement.',
            '/admin/placements?placement='.$placement->id,
            ['placement_id' => $placement->id],
        );
    }

    public function employeeJoined(Placement $placement, Employee $employee): void
    {
        $this->send(
            AdminNotificationEvent::EmployeeJoined,
            'Employee joined',
            'A candidate joined and an employee record was created.',
            '/admin/employees?employee='.$employee->id,
            ['employee_id' => $employee->id, 'placement_id' => $placement->id],
        );
    }

    public function replacementRequested(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementRequested,
            'Replacement requested',
            'A replacement case was requested.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function replacementCandidateIdentified(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementCandidateIdentified,
            'Replacement candidate identified',
            'A replacement candidate was identified.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function replacementCompleted(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementCompleted,
            'Replacement completed',
            'A replacement employee joined. The original employee remains historical.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function replacementCancelled(Replacement $replacement): void
    {
        $this->send(
            AdminNotificationEvent::ReplacementCancelled,
            'Replacement cancelled',
            'A replacement case was cancelled.',
            '/admin/replacements?replacement='.$replacement->id,
            ['replacement_id' => $replacement->id],
        );
    }

    public function invoiceIssued(Invoice $invoice): void
    {
        $this->send(
            AdminNotificationEvent::InvoiceIssued,
            'Invoice issued',
            'Invoice '.($invoice->business_id ?? '').' was issued.',
            '/admin/finance?invoice='.$invoice->id,
            ['invoice_id' => $invoice->id],
        );
    }

    public function paymentReceived(Invoice $invoice, Payment $payment): void
    {
        $this->send(
            AdminNotificationEvent::PaymentReceived,
            'Payment recorded',
            'Payment '.($payment->business_id ?? '').' was recorded against invoice '.($invoice->business_id ?? '').'.',
            '/admin/finance?invoice='.$invoice->id,
            ['invoice_id' => $invoice->id, 'payment_id' => $payment->id],
        );
    }

    public function invoiceCancelled(Invoice $invoice): void
    {
        $this->send(
            AdminNotificationEvent::InvoiceCancelled,
            'Invoice cancelled',
            'Invoice '.($invoice->business_id ?? '').' was cancelled.',
            '/admin/finance?invoice='.$invoice->id,
            ['invoice_id' => $invoice->id],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function send(
        AdminNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');

        DB::afterCommit(function () use ($event, $title, $body, $url, $context, $fingerprint) {
            try {
                $admins = User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('is_active', true)
                    ->whereHas('roles.permissions', fn ($query) => $query->where('name', PermissionName::NotificationsView->value))
                    ->get();
                if ($admins->isEmpty()) {
                    return;
                }

                $fresh = $admins->filter(function (User $admin) use ($fingerprint) {
                    return ! $admin->notifications()
                        ->where('data->fingerprint', $fingerprint)
                        ->exists();
                });

                if ($fresh->isEmpty()) {
                    return;
                }

                Notification::send($fresh, new LifecycleNotification(
                    $event,
                    $title,
                    $body,
                    $url,
                    $context,
                    $fingerprint,
                ));
            } catch (\Throwable $exception) {
                Log::warning('In-app admin notification could not be stored.', [
                    'event' => $event->value,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }
}
