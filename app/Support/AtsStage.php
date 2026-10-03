<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\Placement;

class AtsStage
{
    public const Applied = 'applied';

    public const Screening = 'screening';

    public const Shortlisted = 'shortlisted';

    public const EmployerReview = 'employer_review';

    public const Interview = 'interview';

    public const Selected = 'selected';

    public const Offer = 'offer';

    public const Joined = 'joined';

    public const Closed = 'closed';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (string $value) => [
            'value' => $value,
            'label' => self::label($value),
        ], [
            self::Applied,
            self::Screening,
            self::Shortlisted,
            self::EmployerReview,
            self::Interview,
            self::Selected,
            self::Offer,
            self::Joined,
            self::Closed,
        ]);
    }

    public static function label(string $value): string
    {
        return match ($value) {
            self::Applied => 'Applied / New',
            self::Screening => 'Screening',
            self::Shortlisted => 'Shortlisted',
            self::EmployerReview => 'Employer Review',
            self::Interview => 'Interview',
            self::Selected => 'Selected',
            self::Offer => 'Offer',
            self::Joined => 'Joined',
            self::Closed => 'Rejected / Withdrawn / On Hold',
            default => $value,
        };
    }

    /**
     * @return array{value: string, label: string}
     */
    public static function for(Application $application): array
    {
        $value = self::resolve($application);

        return [
            'value' => $value,
            'label' => self::label($value),
        ];
    }

    public static function resolve(Application $application): string
    {
        $application->loadMissing(['placements', 'submissions', 'screening', 'interviews']);

        $placements = $application->placements;
        $submissions = $application->submissions;
        $hasInterview = $application->interviews->isNotEmpty();

        if ($application->status === ApplicationStatus::Placed
            || $placements->contains(fn (Placement $placement) => $placement->status === PlacementStatus::Joined)) {
            return self::Joined;
        }

        if ($placements->contains(fn (Placement $placement) => in_array($placement->status, [
            PlacementStatus::Offered,
            PlacementStatus::Accepted,
            PlacementStatus::JoiningConfirmed,
        ], true))) {
            return self::Offer;
        }

        if ($application->status === ApplicationStatus::Selected
            || $submissions->contains(fn (EmployerSubmission $submission) => $submission->status === EmployerSubmissionStatus::Selected)) {
            return self::Selected;
        }

        if ($application->status === ApplicationStatus::Interview || $hasInterview) {
            return self::Interview;
        }

        if ($submissions->contains(fn (EmployerSubmission $submission) => in_array($submission->status, [
            EmployerSubmissionStatus::Submitted,
            EmployerSubmissionStatus::UnderReview,
        ], true))) {
            return self::EmployerReview;
        }

        if ($application->status === ApplicationStatus::Shortlisted
            || $application->screening?->stage === ScreeningStage::Shortlisted
            || $application->screeningDecision() === ScreeningDecision::Shortlisted) {
            return self::Shortlisted;
        }

        if (in_array($application->status, [ApplicationStatus::Rejected, ApplicationStatus::Withdrawn], true)
            || in_array($application->screening?->stage, [ScreeningStage::Held, ScreeningStage::Rejected], true)
            || $submissions->contains(fn (EmployerSubmission $submission) => in_array($submission->status, [
                EmployerSubmissionStatus::OnHold,
                EmployerSubmissionStatus::Rejected,
            ], true))) {
            return self::Closed;
        }

        if (in_array($application->screeningStage(), [
            ScreeningStage::Started,
            ScreeningStage::Matched,
            ScreeningStage::Contacted,
            ScreeningStage::Called,
            ScreeningStage::Interviewed,
        ], true)) {
            return self::Screening;
        }

        return self::Applied;
    }
}
