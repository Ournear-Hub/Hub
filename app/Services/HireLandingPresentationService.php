<?php

namespace App\Services;

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\User;
use App\Support\AtsStage;
use App\Support\EmployerApplicationPipeline;

class HireLandingPresentationService
{
    /**
     * @return array<string, mixed>
     */
    public function payloadFor(?User $user): array
    {
        if (! $user instanceof User || ! $user->canAccessHiringWorkspace()) {
            return $this->demonstrationPayload();
        }

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        if (! $profile instanceof EmployerProfile) {
            return $this->demonstrationPayload();
        }

        $summary = collect(EmployerApplicationPipeline::summaryForEmployerApplicationsAts($profile))->keyBy('key');

        $count = fn (string $key): int => (int) ($summary->get($key)['count'] ?? 0);

        $activeJobs = (int) $profile->jobs()->where('active', true)->count();
        $applications = $count('all');
        $shortlisted = $count(AtsStage::Shortlisted);
        $interviews = $count(AtsStage::Interview);
        $offers = $count(AtsStage::Offer) + $count(AtsStage::Selected);
        $hired = $count(AtsStage::Joined);

        $atsStages = [
            ['key' => 'applied', 'label' => 'Applied', 'count' => $count(AtsStage::Applied)],
            ['key' => 'screening', 'label' => 'Screening', 'count' => $count(AtsStage::Screening) + $count(AtsStage::EmployerReview)],
            ['key' => 'shortlisted', 'label' => 'Shortlisted', 'count' => $shortlisted],
            ['key' => 'interview', 'label' => 'Interview', 'count' => $interviews],
            ['key' => 'offer', 'label' => 'Offer', 'count' => $offers],
            ['key' => 'hired', 'label' => 'Hired', 'count' => $hired],
        ];

        return [
            'mode' => 'live',
            'workspace' => [
                'active_jobs' => $activeJobs,
                'applications' => $applications,
                'shortlisted' => $shortlisted,
                'interviews' => $interviews,
                'offers' => $offers,
                'hired' => $hired,
            ],
            'ats_stages' => $atsStages,
            'reports' => [
                'total_applications' => $applications,
                'shortlisted' => $shortlisted,
                'interviews' => $interviews,
                'offers' => $offers,
                'hired' => $hired,
            ],
            'chart' => [
                'title' => 'Hiring Pipeline Snapshot',
                'subtitle' => 'Current pipeline stage counts from your workspace',
                'series' => array_map(fn (array $stage) => [
                    'label' => $stage['label'],
                    'value' => $stage['count'],
                ], $atsStages),
            ],
            'company' => $this->companyPreview($profile, $user),
            'recent_activity' => $this->recentActivity($profile),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function demonstrationPayload(): array
    {
        return [
            'mode' => 'demonstration',
            'workspace' => null,
            'ats_stages' => null,
            'reports' => null,
            'chart' => null,
            'company' => null,
            'recent_activity' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyPreview(EmployerProfile $profile, User $user): array
    {
        $openJobs = (int) $profile->jobs()->publiclyVisible()->count();

        return [
            'company_name' => $profile->displayCompany(),
            'industry' => $profile->displayIndustry() ?: null,
            'location' => $profile->displayLocations() ?: null,
            'about' => filled($profile->about_company) ? (string) $profile->about_company : null,
            'logo_url' => $profile->logoUrl(false),
            'open_jobs' => $openJobs,
            'public_url' => $profile->allowsPublicCompanyPage() ? route('companies.show', $profile, false) : null,
            'company_size' => filled($profile->company_size) ? (string) $profile->company_size : null,
        ];
    }

    /**
     * @return list<array{label: string, detail: string|null, at: string|null}>
     */
    private function recentActivity(EmployerProfile $profile): array
    {
        return $profile->applications()
            ->with('job')
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(function (Application $application): array {
                $stage = AtsStage::for($application);

                return [
                    'label' => $this->activityLabel($stage['value']),
                    'detail' => $application->job?->title,
                    'at' => $application->updated_at?->diffForHumans(),
                ];
            })
            ->all();
    }

    private function activityLabel(string $stage): string
    {
        return match ($stage) {
            AtsStage::Applied => 'New application received',
            AtsStage::Interview => 'Candidate moved to Interview',
            AtsStage::Offer, AtsStage::Selected => 'Offer sent to candidate',
            AtsStage::Joined => 'Candidate joined',
            AtsStage::Shortlisted => 'Candidate shortlisted',
            default => 'Application status updated',
        };
    }
}
