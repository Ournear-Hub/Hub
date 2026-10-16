<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class EmployerApplicationPipeline
{
    /**
     * Authoritative ATS lifecycle for a single job's applicant list (matches {@see AtsStage}).
     *
     * @return list<array{key: string, label: string}>
     */
    public static function employerJobAtsStages(): array
    {
        return [
            ['key' => 'all', 'label' => 'All'],
            ['key' => AtsStage::Applied, 'label' => 'Applied'],
            ['key' => AtsStage::Screening, 'label' => 'Screening'],
            ['key' => AtsStage::Shortlisted, 'label' => 'Shortlisted'],
            ['key' => AtsStage::Interview, 'label' => 'Interview'],
            ['key' => AtsStage::Selected, 'label' => 'Selected'],
            ['key' => AtsStage::Offer, 'label' => 'Offer'],
            ['key' => AtsStage::Joined, 'label' => 'Joined'],
            ['key' => AtsStage::Closed, 'label' => 'Closed'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function employerJobAtsStageKeys(): array
    {
        return array_column(self::employerJobAtsStages(), 'key');
    }

    public static function normalizeLegacyJobStageKey(string $stage): string
    {
        return match ($stage) {
            'submitted' => AtsStage::Applied,
            'under_review' => AtsStage::Screening,
            'rejected', 'withdrawn' => AtsStage::Closed,
            default => $stage,
        };
    }

    public static function normalizeEmployerAtsStageKey(string $resolvedAtsStage): string
    {
        return $resolvedAtsStage === AtsStage::EmployerReview
            ? AtsStage::Screening
            : $resolvedAtsStage;
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function summaryForJobAts(Job $job): array
    {
        $totals = [];
        foreach (self::employerJobAtsStages() as $definition) {
            $totals[$definition['key']] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'count' => 0,
            ];
        }

        $applications = $job->applications()
            ->with(['placements', 'submissions', 'screening', 'interviews'])
            ->get();

        foreach ($applications as $application) {
            $bucket = self::normalizeEmployerAtsStageKey(AtsStage::resolve($application));
            if (! isset($totals[$bucket])) {
                continue;
            }

            $totals['all']['count']++;
            $totals[$bucket]['count']++;
        }

        return array_values($totals);
    }

    /**
     * @return list<int>
     */
    public static function applicationIdsForJobAtsStage(Job $job, string $stage): array
    {
        if ($stage === '' || $stage === 'all') {
            return [];
        }

        return $job->applications()
            ->with(['placements', 'submissions', 'screening', 'interviews'])
            ->get()
            ->filter(function (Application $application) use ($stage): bool {
                return self::normalizeEmployerAtsStageKey(AtsStage::resolve($application)) === $stage;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Application>|Relation<Application, Job, *>  $query
     */
    public static function applyEmployerJobAtsStageFilter(Builder|Relation $query, Job $job, string $stage): void
    {
        if ($stage === '' || $stage === 'all') {
            return;
        }

        $ids = self::applicationIdsForJobAtsStage($job, $stage);
        $query->whereIn($query->qualifyColumn('id'), $ids !== [] ? $ids : [0]);
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function summaryForEmployerApplicationsAts(EmployerProfile $profile): array
    {
        $totals = [];
        foreach (self::employerJobAtsStages() as $definition) {
            $totals[$definition['key']] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'count' => 0,
            ];
        }

        $applications = $profile->applications()
            ->with(['placements', 'submissions', 'screening', 'interviews'])
            ->get();

        foreach ($applications as $application) {
            $bucket = self::normalizeEmployerAtsStageKey(AtsStage::resolve($application));
            if (! isset($totals[$bucket])) {
                continue;
            }

            $totals['all']['count']++;
            $totals[$bucket]['count']++;
        }

        return array_values($totals);
    }

    /**
     * @return list<int>
     */
    public static function applicationIdsForEmployerAtsStage(EmployerProfile $profile, string $stage): array
    {
        if ($stage === '' || $stage === 'all') {
            return [];
        }

        return $profile->applications()
            ->with(['placements', 'submissions', 'screening', 'interviews'])
            ->get()
            ->filter(function (Application $application) use ($stage): bool {
                return self::normalizeEmployerAtsStageKey(AtsStage::resolve($application)) === $stage;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Application>|Relation<Application, EmployerProfile, *>  $query
     */
    public static function applyEmployerApplicationsAtsStageFilter(Builder|Relation $query, EmployerProfile $profile, string $stage): void
    {
        if ($stage === '' || $stage === 'all') {
            return;
        }

        $ids = self::applicationIdsForEmployerAtsStage($profile, $stage);
        $query->whereIn($query->qualifyColumn('id'), $ids !== [] ? $ids : [0]);
    }

    /**
     * @return list<array{key: string, label: string, statuses: list<ApplicationStatus>}>
     */
    public static function stages(): array
    {
        return [
            ['key' => 'all', 'label' => 'All', 'statuses' => []],
            ['key' => 'submitted', 'label' => 'Submitted', 'statuses' => [ApplicationStatus::New]],
            ['key' => 'under_review', 'label' => 'Under review', 'statuses' => [ApplicationStatus::Reviewing]],
            ['key' => 'shortlisted', 'label' => 'Shortlisted', 'statuses' => [ApplicationStatus::Shortlisted]],
            ['key' => 'interview', 'label' => 'Interview', 'statuses' => [ApplicationStatus::Interview]],
            ['key' => 'selected', 'label' => 'Selected', 'statuses' => [ApplicationStatus::Selected, ApplicationStatus::Placed]],
            ['key' => 'rejected', 'label' => 'Rejected', 'statuses' => [ApplicationStatus::Rejected]],
            ['key' => 'withdrawn', 'label' => 'Withdrawn', 'statuses' => [ApplicationStatus::Withdrawn]],
        ];
    }

    /**
     * @return list<ApplicationStatus>
     */
    public static function statusesForStage(string $stage): array
    {
        foreach (self::stages() as $definition) {
            if ($definition['key'] === $stage) {
                return $definition['statuses'];
            }
        }

        return [];
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function summaryForJob(Job $job): array
    {
        $totals = [];
        foreach (self::stages() as $definition) {
            $totals[$definition['key']] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'count' => 0,
            ];
        }

        $rows = $job->applications()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $rawStatus = $row->status;
            $status = $rawStatus instanceof ApplicationStatus
                ? $rawStatus
                : ApplicationStatus::tryFrom((string) $rawStatus);
            if (! $status instanceof ApplicationStatus) {
                continue;
            }

            $totals['all']['count'] += (int) $row->aggregate;

            foreach (self::stages() as $definition) {
                if ($definition['key'] === 'all') {
                    continue;
                }

                if (in_array($status, $definition['statuses'], true)) {
                    $totals[$definition['key']]['count'] += (int) $row->aggregate;
                }
            }
        }

        return array_values($totals);
    }

    /**
     * Talent Pool navigation stages (employer-wide candidates from owned job applications).
     *
     * "In review" maps to {@see ApplicationStatus::Reviewing} (no separate Contacted status on applications).
     *
     * @return list<array{key: string, label: string, statuses: list<ApplicationStatus>}>
     */
    public static function talentPoolStages(): array
    {
        return [
            ['key' => 'all', 'label' => 'All Candidates', 'statuses' => []],
            ['key' => 'shortlisted', 'label' => 'Shortlisted', 'statuses' => [ApplicationStatus::Shortlisted]],
            ['key' => 'under_review', 'label' => 'In review', 'statuses' => [ApplicationStatus::Reviewing]],
            ['key' => 'interview', 'label' => 'Interviewed', 'statuses' => [ApplicationStatus::Interview]],
            ['key' => 'offered', 'label' => 'Offered', 'statuses' => [ApplicationStatus::Selected]],
            ['key' => 'joined', 'label' => 'Joined', 'statuses' => [ApplicationStatus::Placed]],
        ];
    }

    /**
     * @return list<ApplicationStatus>
     */
    public static function statusesForTalentPoolStage(string $stage): array
    {
        foreach (self::talentPoolStages() as $definition) {
            if ($definition['key'] === $stage) {
                return $definition['statuses'];
            }
        }

        return [];
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function summaryForEmployer(EmployerProfile $profile): array
    {
        $totals = [];
        foreach (self::talentPoolStages() as $definition) {
            $totals[$definition['key']] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'count' => 0,
            ];
        }

        $rows = $profile->applications()
            ->toBase()
            ->selectRaw('applications.status as status, COUNT(*) as aggregate')
            ->groupBy('applications.status')
            ->get();

        foreach ($rows as $row) {
            $rawStatus = $row->status;
            $status = $rawStatus instanceof ApplicationStatus
                ? $rawStatus
                : ApplicationStatus::tryFrom((string) $rawStatus);
            if (! $status instanceof ApplicationStatus) {
                continue;
            }

            $totals['all']['count'] += (int) $row->aggregate;

            foreach (self::talentPoolStages() as $definition) {
                if ($definition['key'] === 'all') {
                    continue;
                }

                if (in_array($status, $definition['statuses'], true)) {
                    $totals[$definition['key']]['count'] += (int) $row->aggregate;
                }
            }
        }

        return array_values($totals);
    }

    /**
     * @param  Builder<\App\Models\Application>|Relation<\App\Models\Application, \App\Models\EmployerProfile, *>  $query
     */
    public static function applyTalentPoolStageFilter(Builder|Relation $query, string $stage): void
    {
        if ($stage === '' || $stage === 'all') {
            return;
        }

        $statuses = self::statusesForTalentPoolStage($stage);
        if ($statuses === []) {
            return;
        }

        $query->whereIn('status', array_map(fn (ApplicationStatus $case) => $case->value, $statuses));
    }

    /**
     * Job-applicant pipeline counts across all of an employer's applications.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function summaryForEmployerApplications(EmployerProfile $profile): array
    {
        $totals = [];
        foreach (self::stages() as $definition) {
            $totals[$definition['key']] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'count' => 0,
            ];
        }

        $rows = $profile->applications()
            ->toBase()
            ->selectRaw('applications.status as status, COUNT(*) as aggregate')
            ->groupBy('applications.status')
            ->get();

        foreach ($rows as $row) {
            $rawStatus = $row->status;
            $status = $rawStatus instanceof ApplicationStatus
                ? $rawStatus
                : ApplicationStatus::tryFrom((string) $rawStatus);
            if (! $status instanceof ApplicationStatus) {
                continue;
            }

            $totals['all']['count'] += (int) $row->aggregate;

            foreach (self::stages() as $definition) {
                if ($definition['key'] === 'all') {
                    continue;
                }

                if (in_array($status, $definition['statuses'], true)) {
                    $totals[$definition['key']]['count'] += (int) $row->aggregate;
                }
            }
        }

        return array_values($totals);
    }

    /**
     * @param  Builder<\App\Models\Application>|Relation<\App\Models\Application, \App\Models\EmployerProfile, *>  $query
     */
    public static function applyJobStageFilter(Builder|Relation $query, string $stage): void
    {
        if ($stage === '' || $stage === 'all') {
            return;
        }

        $statuses = self::statusesForStage($stage);
        if ($statuses === []) {
            return;
        }

        $query->whereIn('status', array_map(fn (ApplicationStatus $case) => $case->value, $statuses));
    }

    public static function stageLabelForApplication(ApplicationStatus $status): string
    {
        return $status->toCandidateStatusLabel();
    }
}
