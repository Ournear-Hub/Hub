<?php

namespace App\Services;

use App\Mail\ApplicationSubmittedAdminMail;
use App\Mail\ApplicationSubmittedApplicantMail;
use App\Mail\ContactEnquirySubmittedAdminMail;
use App\Mail\ContactEnquirySubmittedSubmitterMail;
use App\Mail\EmployerEnquirySubmittedAdminMail;
use App\Mail\EmployerEnquirySubmittedEmployerMail;
use App\Models\Application;
use App\Models\ContactEnquiry;
use App\Models\EmployerEnquiry;
use App\Support\MailOutcome;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubmissionMailService
{
    public function __construct(private WebsiteSettingService $settings) {}

    public function notifyApplication(Application $application): MailOutcome
    {
        $application->loadMissing('job');
        $this->sendToAdmins(new ApplicationSubmittedAdminMail($application));

        return new MailOutcome(
            $this->sendToRecipient($application->email, new ApplicationSubmittedApplicantMail($application))
        );
    }

    public function notifyEmployerEnquiry(EmployerEnquiry $enquiry): MailOutcome
    {
        $this->sendToAdmins(new EmployerEnquirySubmittedAdminMail($enquiry));

        return new MailOutcome(
            $this->sendToRecipient($enquiry->email, new EmployerEnquirySubmittedEmployerMail($enquiry))
        );
    }

    public function notifyContactEnquiry(ContactEnquiry $enquiry): MailOutcome
    {
        $this->sendToAdmins(new ContactEnquirySubmittedAdminMail($enquiry));

        if (! filled($enquiry->email)) {
            return new MailOutcome(false);
        }

        return new MailOutcome(
            $this->sendToRecipient((string) $enquiry->email, new ContactEnquirySubmittedSubmitterMail($enquiry))
        );
    }

    public function deliversToInbox(): bool
    {
        $mailer = (string) config('mail.default');

        return ! in_array($mailer, ['log', 'array'], true);
    }

    private function sendToAdmins(Mailable $mailable): void
    {
        $recipients = $this->settings->notificationRecipients();
        if ($recipients === []) {
            return;
        }

        $this->sendToRecipient($recipients, $mailable);
    }

    /**
     * @param  string|list<string>  $recipients
     */
    private function sendToRecipient(string|array $recipients, Mailable $mailable): bool
    {
        $addresses = is_array($recipients) ? $recipients : [$recipients];
        $addresses = array_values(array_filter($addresses, fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL)));

        if ($addresses === []) {
            return false;
        }

        try {
            Mail::to($addresses)->send($mailable);

            return $this->deliversToInbox();
        } catch (\Throwable $exception) {
            Log::warning('Submission email could not be sent.', [
                'mailable' => $mailable::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
