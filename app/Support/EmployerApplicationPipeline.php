<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class EmployerApplicationPipeline
{
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
}
