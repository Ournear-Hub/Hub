<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\UserPhotoService;

class PublicSocialSearchService
{
    public function __construct(private UserPhotoService $photos) {}
    private const MAX_JOBS = 8;

    private const MAX_COMPANIES = 8;

    private const MAX_PROFILES = 8;

    /**
     * Header discovery search: public companies and shareable profiles only (no job listings).
     *
     * @return array{jobs: list<array<string, mixed>>, companies: list<array<string, mixed>>, profiles: list<array<string, mixed>>}
     */
    public function discoverySearch(string $keyword): array
    {
        $results = $this->search($keyword);
        $results['jobs'] = [];

        return $results;
    }

    /**
     * @return array{jobs: list<array<string, mixed>>, companies: list<array<string, mixed>>, profiles: list<array<string, mixed>>}
     */
    public function search(string $keyword): array
    {
        $term = trim($keyword);
        if ($term === '' || mb_strlen($term) < 2) {
            return ['jobs' => [], 'companies' => [], 'profiles' => []];
        }

        $like = '%'.$term.'%';

        $jobs = Job::query()
            ->with(['employer.user', 'classifiedLocation'])
            ->publiclyVisible()
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('project', 'like', $like);
            })
            ->latest('posted_date')
            ->limit(self::MAX_JOBS)
            ->get()
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->employer?->publicDisplayCompany(),
                'location' => $job->classifiedLocation?->name,
                'url' => route('jobs.show', $job),
            ])
            ->values()
            ->all();

        $companies = EmployerProfile::query()
            ->with(['user', 'locations'])
            ->where('company_name', 'like', $like)
            ->limit(self::MAX_COMPANIES * 2)
            ->get()
            ->filter(
                fn (EmployerProfile $employer) => $employer->user?->is_active === true
                    && $employer->shareAllows('enabled'),
            )
            ->take(self::MAX_COMPANIES)
            ->map(fn (EmployerProfile $employer) => [
                'id' => $employer->id,
                'name' => $employer->publicDisplayCompany(),
                'industry' => $employer->shareAllows('industry_size') ? $employer->displayIndustry() : null,
                'location' => $employer->shareAllows('locations')
                    ? $employer->locations->pluck('name')->filter()->take(2)->implode(', ') ?: null
                    : null,
                'logo_url' => $employer->logoUrl(false),
                'url' => route('companies.show', $employer),
            ])
            ->values()
            ->all();

        $profiles = User::query()
            ->with('candidateProfile')
            ->where('is_active', true)
            ->whereNotNull('username')
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like);
            })
            ->whereHas('candidateProfile')
            ->limit(self::MAX_PROFILES * 4)
            ->get()
            ->filter(function (User $user) {
                if (! $user->isCandidate()) {
                    return false;
                }

                $profile = $user->candidateProfile;

                return $profile instanceof CandidateProfile && $profile->shareAllows('enabled');
            })
            ->take(self::MAX_PROFILES)
            ->map(function (User $user) {
                $profile = $user->candidateProfile;
                assert($profile instanceof CandidateProfile);

                $headline = null;
                if ($profile->shareAllows('headline')) {
                    $headline = $user->professional_headline ?: $profile->current_designation;
                } elseif ($profile->shareAllows('current_role')) {
                    $headline = $profile->current_designation;
                }

                return [
                    'name' => $user->name,
                    'headline' => $headline,
                    'photo_url' => $this->photos->publicUrl($user->photo_path),
                    'url' => route('profiles.show', $user),
                ];
            })
            ->values()
            ->all();

        return [
            'jobs' => $jobs,
            'companies' => $companies,
            'profiles' => $profiles,
        ];
    }
}
