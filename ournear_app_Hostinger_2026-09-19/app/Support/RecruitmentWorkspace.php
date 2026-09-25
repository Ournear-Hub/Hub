<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\ReplacementStatus;
use App\Enums\ConversationContextType;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Employee;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\PlacementOffer;
use App\Models\Replacement;

final class RecruitmentWorkspace
{
    public const NOT_RECORDED = 'Not recorded yet';

    public const NOT_AVAILABLE = 'Not available in V1.1';

    public static function employerUrl(?EmployerProfile $employer): ?string
    {
        return $employer ? route('admin.employers.index', ['employer' => $employer->id], false) : null;
    }

    public static function jobUrl(?Job $job): ?string
    {
        return $job?->slug ? route('admin.jobs.index', ['job' => $job->slug], false) : null;
    }

    public static function candidateUrl(?CandidateProfile $profile): ?string
    {
        return $profile ? route('admin.candidates.index', ['candidate' => $profile->id], false) : null;
    }

    public static function applicationUrl(?Application $application): ?string
    {
        return $application ? route('admin.applications.index', ['application' => $application->id], false) : null;
    }

    public static function screeningUrl(?Job $job, ?Application $application = null): ?string
    {
        if ($job === null && $application === null) {
            return null;
        }

        $params = array_filter([
            'job_id' => $job?->id,
            'application' => $application?->id,
        ]);

        return route('admin.screening.index', $params, false);
    }

    public static function shortlistUrl(?Application $application, ?Job $job = null): ?string
    {
        $params = array_filter([
            'job_id' => $job?->id,
            'application' => $application?->id,
        ]);

        return route('admin.shortlisted.index', $params, false);
    }

    public static function submissionUrl(?EmployerSubmission $submission): ?string
    {
        return $submission ? route('admin.submissions.index', ['submission' => $submission->id], false) : null;
    }

    public static function placementUrl(?Placement $placement): ?string
    {
        return $placement ? route('admin.placements.index', ['placement' => $placement->id], false) : null;
    }

    public static function employeeUrl(?Employee $employee): ?string
    {
        return $employee ? route('admin.employees.index', ['employee' => $employee->id], false) : null;
    }

    public static function replacementUrl(?Replacement $replacement): ?string
    {
        return $replacement ? route('admin.replacements.index', ['replacement' => $replacement->id], false) : null;
    }

    public static function invoiceUrl(?Invoice $invoice): ?string
    {
        return $invoice ? route('admin.finance.index', ['invoice' => $invoice->id, 'view' => 'invoices'], false) : null;
    }

    public static function paymentUrl(?Payment $payment): ?string
    {
        return $payment ? route('admin.finance.index', ['payment' => $payment->id, 'view' => 'payments'], false) : null;
    }

    /**
     * @return array{key: string, label: string, value: ?string, href: ?string, status: ?string}
     */
    public static function step(string $key, string $label, ?string $value, ?string $href = null, ?string $status = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'href' => $href,
            'status' => $status,
        ];
    }

    public static function responseLabel(EmployerSubmissionStatus $status): string
    {
        return match ($status) {
            EmployerSubmissionStatus::Submitted => 'Awaiting employer response',
            EmployerSubmissionStatus::UnderReview => 'Under Review',
            EmployerSubmissionStatus::Selected => 'Selected',
            EmployerSubmissionStatus::Rejected => 'Rejected',
            EmployerSubmissionStatus::OnHold => 'On Hold',
        };
    }

    public static function selectionOutcome(EmployerSubmissionStatus $status): string
    {
        return match ($status) {
            EmployerSubmissionStatus::Selected => 'Selected',
            EmployerSubmissionStatus::Rejected => 'Rejected',
            EmployerSubmissionStatus::OnHold => 'On Hold',
            EmployerSubmissionStatus::UnderReview => 'Under Review',
            EmployerSubmissionStatus::Submitted => 'Awaiting employer response',
        };
    }

    public static function joiningStatus(PlacementStatus $status): string
    {
        return match ($status) {
            PlacementStatus::Joined => 'Joined',
            PlacementStatus::Declined => 'Did not join',
            PlacementStatus::Withdrawn => 'Cancelled',
            default => 'Pending',
        };
    }

    public static function joiningStatusKey(PlacementStatus $status): string
    {
        return match ($status) {
            PlacementStatus::Joined => 'joined',
            PlacementStatus::Declined => 'did_not_join',
            PlacementStatus::Withdrawn => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * @return list<string>
     */
    public static function placementStatusesForJoiningFilter(string $key): array
    {
        return match ($key) {
            'joined' => [PlacementStatus::Joined->value],
            'did_not_join' => [PlacementStatus::Declined->value],
            'cancelled' => [PlacementStatus::Withdrawn->value],
            'pending' => [
                PlacementStatus::Open->value,
                PlacementStatus::Offered->value,
                PlacementStatus::Accepted->value,
                PlacementStatus::JoiningConfirmed->value,
            ],
            default => [],
        };
    }

    public static function offerStatus(?PlacementOffer $offer): string
    {
        if ($offer === null) {
            return self::NOT_RECORDED;
        }

        if ($offer->withdrawn_at) {
            return 'Withdrawn';
        }

        if ($offer->declined_at) {
            return 'Declined';
        }

        if ($offer->accepted_at) {
            return 'Accepted';
        }

        return 'Offered';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function submissionChain(EmployerSubmission $submission): array
    {
        $application = $submission->application;
        $job = $submission->job ?? $application?->job;
        $profile = $application?->user?->candidateProfile;
        $placement = $submission->placement;

        return [
            self::step('candidate', 'Candidate', $application?->full_name, self::candidateUrl($profile)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application), $application?->status?->value),
            self::step('screening', 'Screening', $application ? 'Open screening record' : null, self::screeningUrl($job, $application)),
            self::step(
                'shortlisted',
                'Shortlisted',
                $application && $application->status === ApplicationStatus::Shortlisted ? $application->business_id : null,
                self::shortlistUrl($application, $job),
                $application?->status?->value,
            ),
            self::step('submission', 'Employer Submission', $submission->business_id, self::submissionUrl($submission), $submission->status->value),
            self::step('response', 'Employer Response', self::responseLabel($submission->status), self::submissionUrl($submission), $submission->status->value),
            self::step('selection', 'Selection', self::selectionOutcome($submission->status), self::submissionUrl($submission), $submission->status->value),
            self::step('placement', 'Placement', $placement?->business_id, self::placementUrl($placement), $placement?->status?->value),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function placementChain(Placement $placement): array
    {
        $application = $placement->application;
        $profile = $application?->user?->candidateProfile;
        $submission = $placement->submission;
        $offer = $placement->offer;
        $employee = $placement->employee;
        $originating = $placement->originatingReplacements->sortByDesc('id')->first();

        return [
            self::step('candidate', 'Candidate', $application?->full_name, self::candidateUrl($profile)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application), $application?->status?->value),
            self::step('job', 'Job', $placement->job?->title, self::jobUrl($placement->job)),
            self::step('employer', 'Employer', $placement->employer?->displayCompany(), self::employerUrl($placement->employer)),
            self::step('selection', 'Selection', $submission?->business_id, self::submissionUrl($submission), $submission?->status?->value),
            self::step('placement', 'Placement', $placement->business_id, self::placementUrl($placement), $placement->status->value),
            self::step('offer', 'Offer', $offer ? self::offerStatus($offer) : null, self::placementUrl($placement), $offer ? self::offerStatus($offer) : null),
            self::step('joining', 'Joining', self::joiningStatus($placement->status), self::placementUrl($placement), self::joiningStatus($placement->status)),
            self::step('employee', 'Employee', $employee?->business_id, self::employeeUrl($employee)),
            self::step('replacement', 'Replacement', $originating?->business_id, self::replacementUrl($originating), $originating?->status?->value),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function employeeChain(Employee $employee): array
    {
        $application = $employee->application;
        $profile = $employee->candidateProfile ?? $application?->user?->candidateProfile;
        $placement = $employee->placement;
        $offer = $placement?->offer;

        return [
            self::step('candidate', 'Candidate', $application?->full_name, self::candidateUrl($profile)),
            self::step('employer', 'Employer', $employee->employer?->displayCompany(), self::employerUrl($employee->employer)),
            self::step('job', 'Job', $employee->job?->title, self::jobUrl($employee->job)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application)),
            self::step('placement', 'Placement', $placement?->business_id, self::placementUrl($placement), $placement?->status?->value),
            self::step('offer', 'Offer', $offer ? self::offerStatus($offer) : null, self::placementUrl($placement)),
            self::step('joining', 'Joining', $employee->joined_at?->toDateTimeString(), self::employeeUrl($employee), 'Joined'),
            self::step('employee', 'Employee', $employee->business_id, self::employeeUrl($employee), 'Joined'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function replacementChain(Replacement $replacement): array
    {
        $originalApplication = $replacement->originalApplication;
        $originalProfile = $originalApplication?->user?->candidateProfile
            ?? $replacement->originalEmployee?->candidateProfile
            ?? $replacement->originalEmployee?->application?->user?->candidateProfile;
        $replacementApplication = $replacement->replacementApplication;
        $replacementProfile = $replacementApplication?->user?->candidateProfile;

        return [
            self::step('original_placement', 'Original Placement', $replacement->originalPlacement?->business_id, self::placementUrl($replacement->originalPlacement), $replacement->originalPlacement?->status?->value),
            self::step('original_candidate', 'Original Candidate', $originalApplication?->full_name, self::candidateUrl($originalProfile)),
            self::step('employer', 'Employer', $replacement->employer?->displayCompany(), self::employerUrl($replacement->employer)),
            self::step('job', 'Job', $replacement->job?->title, self::jobUrl($replacement->job)),
            self::step('joining', 'Joining / Employee', $replacement->originalEmployee?->business_id, self::employeeUrl($replacement->originalEmployee), 'Joined'),
            self::step('request', 'Replacement Request', $replacement->business_id, self::replacementUrl($replacement), $replacement->status->value),
            self::step('reason', 'Reason', $replacement->reason->value),
            self::step('eligibility', 'Eligibility', self::replacementEligibility($replacement)),
            self::step('replacement_candidate', 'Replacement Candidate', $replacementApplication?->full_name, self::candidateUrl($replacementProfile)),
            self::step('replacement_selection', 'Replacement Selection', $replacement->replacementSubmission?->business_id, self::submissionUrl($replacement->replacementSubmission), $replacement->replacementSubmission?->status?->value),
            self::step('replacement_placement', 'Replacement Placement', $replacement->replacementPlacement?->business_id, self::placementUrl($replacement->replacementPlacement), $replacement->replacementPlacement?->status?->value),
            self::step('closure', 'Closure', $replacement->completed_at?->toDateTimeString() ?? $replacement->cancelled_at?->toDateTimeString(), self::replacementUrl($replacement), $replacement->status->isTerminal() ? $replacement->status->value : null),
        ];
    }

    public static function replacementEligibility(Replacement $replacement): string
    {
        if ($replacement->status === ReplacementStatus::Cancelled) {
            return 'Not Eligible';
        }

        if ($replacement->status === ReplacementStatus::Completed) {
            return 'Closed';
        }

        return $replacement->canIdentifyCandidate() ? 'Eligible' : 'Not Eligible';
    }

    public static function replacementOperationalStage(Replacement $replacement): string
    {
        $status = $replacement->status;

        if ($status === ReplacementStatus::Completed || $status === ReplacementStatus::Cancelled) {
            return 'Closed';
        }

        if ($status === ReplacementStatus::Placed || $replacement->replacement_placement_id) {
            return 'Replacement Placed';
        }

        if ($replacement->replacementSubmission?->status === EmployerSubmissionStatus::Selected) {
            return 'Candidate Selected';
        }

        if ($replacement->replacement_application_id) {
            return 'Candidate Identified';
        }

        if ($status === ReplacementStatus::InProgress) {
            return 'Under Review';
        }

        return 'Requested';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function replacementWorkflow(Replacement $replacement): array
    {
        $stage = self::replacementOperationalStage($replacement);
        $order = [
            'Requested',
            'Under Review',
            'Eligible / Not Eligible',
            'Candidate Identified',
            'Candidate Selected',
            'Replacement Placed',
            'Closed',
        ];

        $currentIndex = match ($stage) {
            'Requested' => 0,
            'Under Review' => 1,
            'Candidate Identified' => 3,
            'Candidate Selected' => 4,
            'Replacement Placed' => 5,
            'Closed' => 6,
            default => 0,
        };

        return array_map(function (string $label, int $index) use ($currentIndex, $replacement, $stage) {
            $complete = $index < $currentIndex;
            $current = $index === $currentIndex;

            if ($label === 'Eligible / Not Eligible') {
                return [
                    'key' => 'eligibility',
                    'label' => $label,
                    'value' => self::replacementEligibility($replacement),
                    'complete' => $complete || $currentIndex >= 2,
                    'current' => $currentIndex === 0 || $currentIndex === 1,
                    'maps_to' => $replacement->status->value,
                ];
            }

            return [
                'key' => strtolower(str_replace([' / ', ' '], ['_', '_'], $label)),
                'label' => $label,
                'value' => $current ? $stage : ($complete ? $label : null),
                'complete' => $complete,
                'current' => $current,
                'maps_to' => $replacement->status->value,
            ];
        }, $order, array_keys($order));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function invoiceChain(Invoice $invoice): array
    {
        $application = $invoice->application;
        $profile = $application?->user?->candidateProfile;

        return [
            self::step('invoice', 'Invoice', $invoice->business_id, self::invoiceUrl($invoice), $invoice->status->value),
            self::step('candidate', 'Candidate / customer', $application?->full_name, self::candidateUrl($profile)),
            self::step('employer', 'Employer', $invoice->employer?->displayCompany(), self::employerUrl($invoice->employer)),
            self::step('job', 'Job', $invoice->job?->title, self::jobUrl($invoice->job)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application), $application?->status?->value),
            self::step('submission', 'Employer Submission', $invoice->submission?->business_id, self::submissionUrl($invoice->submission), $invoice->submission?->status?->value),
            self::step('placement', 'Placement', $invoice->placement?->business_id, self::placementUrl($invoice->placement), $invoice->placement?->status?->value),
            self::step('employee', 'Employee', $invoice->employee?->business_id, self::employeeUrl($invoice->employee)),
            self::step('replacement', 'Replacement', $invoice->replacement?->business_id, self::replacementUrl($invoice->replacement), $invoice->replacement?->status?->value),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function paymentChain(Payment $payment): array
    {
        $invoice = $payment->invoice;
        $application = $invoice?->application;
        $profile = $application?->user?->candidateProfile;

        return [
            self::step('invoice', 'Invoice', $invoice?->business_id, self::invoiceUrl($invoice), $invoice?->status?->value),
            self::step('payment', 'Payment', $payment->business_id, self::paymentUrl($payment), $payment->operationalStatus()),
            self::step('candidate', 'Candidate / customer', $application?->full_name, self::candidateUrl($profile)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application), $application?->status?->value),
            self::step('placement', 'Placement', $invoice?->placement?->business_id, self::placementUrl($invoice?->placement), $invoice?->placement?->status?->value),
            self::step('job', 'Job', $invoice?->job?->title, self::jobUrl($invoice?->job)),
            self::step('employer', 'Employer', $invoice?->employer?->displayCompany(), self::employerUrl($invoice?->employer)),
            self::step('employee', 'Employee', $invoice?->employee?->business_id, self::employeeUrl($invoice?->employee)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function conversationChain(?ConversationContextType $type, ?int $id): array
    {
        if ($type === null || $id === null || $id <= 0) {
            return [];
        }

        return match ($type) {
            ConversationContextType::Employer => self::employerContextChain($id),
            ConversationContextType::Job => self::jobContextChain($id),
            ConversationContextType::Application => self::applicationContextChain($id),
            ConversationContextType::Submission => self::submissionContextChain($id),
            ConversationContextType::Placement => self::placementContextById($id),
            ConversationContextType::Employee => self::employeeContextChain($id),
            ConversationContextType::Replacement => self::replacementContextChain($id),
            ConversationContextType::Invoice => self::invoiceContextChain($id),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function employerContextChain(int $id): array
    {
        $employer = EmployerProfile::query()->with('user')->find($id);

        return [
            self::step('employer', 'Employer', $employer?->displayCompany(), self::employerUrl($employer)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function jobContextChain(int $id): array
    {
        $job = Job::query()->with('employer.user')->find($id);

        return [
            self::step('employer', 'Employer', $job?->employer?->displayCompany(), self::employerUrl($job?->employer)),
            self::step('job', 'Job', $job?->title, self::jobUrl($job)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function applicationContextChain(int $id): array
    {
        $application = Application::query()->with(['job.employer.user', 'user.candidateProfile'])->find($id);
        $profile = $application?->user?->candidateProfile;

        return [
            self::step('employer', 'Employer', $application?->job?->employer?->displayCompany(), self::employerUrl($application?->job?->employer)),
            self::step('job', 'Job', $application?->job?->title, self::jobUrl($application?->job)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application), $application?->status?->value),
            self::step('candidate', 'Candidate', $application?->full_name, self::candidateUrl($profile)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function submissionContextChain(int $id): array
    {
        $submission = EmployerSubmission::query()
            ->with(['application.user.candidateProfile', 'employer.user', 'job'])
            ->find($id);

        if ($submission === null) {
            return [
                self::step('candidate', 'Candidate', null),
                self::step('application', 'Application', null),
                self::step('submission', 'Employer Submission', null),
            ];
        }

        $application = $submission->application;
        $profile = $application?->user?->candidateProfile;

        return [
            self::step('candidate', 'Candidate', $application?->full_name, self::candidateUrl($profile)),
            self::step('application', 'Application', $application?->business_id, self::applicationUrl($application), $application?->status?->value),
            self::step('employer', 'Employer', $submission->employer?->displayCompany(), self::employerUrl($submission->employer)),
            self::step('job', 'Job', $submission->job?->title, self::jobUrl($submission->job)),
            self::step('submission', 'Employer Submission', $submission->business_id, self::submissionUrl($submission), $submission->status->value),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function placementContextById(int $id): array
    {
        $placement = Placement::query()
            ->with(['application.user.candidateProfile', 'employer.user', 'job', 'submission'])
            ->find($id);

        if ($placement === null) {
            return [self::step('placement', 'Placement', null)];
        }

        return self::placementChain($placement);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function employeeContextChain(int $id): array
    {
        $employee = Employee::query()
            ->with(['application.user.candidateProfile', 'employer.user', 'job', 'placement'])
            ->find($id);

        if ($employee === null) {
            return [self::step('employee', 'Employee', null)];
        }

        return self::employeeChain($employee);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function replacementContextChain(int $id): array
    {
        $replacement = Replacement::query()
            ->with(['originalApplication.user.candidateProfile', 'employer.user', 'job'])
            ->find($id);

        if ($replacement === null) {
            return [self::step('replacement', 'Replacement', null)];
        }

        return self::replacementChain($replacement);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function invoiceContextChain(int $id): array
    {
        $invoice = Invoice::query()
            ->with(['application.user.candidateProfile', 'employer.user', 'job', 'submission', 'placement', 'employee', 'replacement'])
            ->find($id);

        if ($invoice === null) {
            return [self::step('invoice', 'Invoice', null)];
        }

        return self::invoiceChain($invoice);
    }
}
