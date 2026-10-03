<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\Location;
use App\Models\User;
use App\Services\WebsiteContentService;
use App\Support\PublicContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPlatformController extends Controller
{
    public function companies(Request $request): Response
    {
        $keyword = $request->string('keyword')->toString();
        $industry = $request->string('industry')->toString();
        $location = $request->string('location')->toString();

        $companies = EmployerProfile::query()
            ->with(['user', 'locations'])
            ->when($keyword !== '', fn ($query) => $query->where('company_name', 'like', '%'.$keyword.'%'))
            ->when($industry !== '', fn ($query) => $query->where('industry', $industry))
            ->when(
                $location !== '',
                fn ($query) => $query->whereHas(
                    'locations',
                    fn ($locationQuery) => $locationQuery->where('name', $location),
                ),
            )
            ->withCount(['jobs as open_jobs_count' => fn ($query) => $query->publiclyVisible()])
            ->orderByDesc('open_jobs_count')
            ->orderBy('company_name')
            ->limit(120)
            ->get()
            ->filter(
                fn (EmployerProfile $employer) => $employer->user?->is_active === true
                    && $employer->shareAllows('enabled'),
            )
            ->take(60)
            ->map(fn (EmployerProfile $employer) => [
                'id' => $employer->id,
                'name' => $employer->publicDisplayCompany(),
                'industry' => $employer->shareAllows('industry_size') ? $employer->displayIndustry() : null,
                'about' => $employer->shareAllows('about') ? $employer->about_company : null,
                'location' => $employer->shareAllows('locations')
                    ? $employer->locations->pluck('name')->filter()->implode(', ') ?: null
                    : null,
                'logo_url' => $employer->user?->photoUrl(),
                'ournear_verified' => $employer->ournearVerified(),
                'open_jobs' => (int) $employer->open_jobs_count,
                'url' => route('companies.show', $employer),
            ])
            ->values();

        return Inertia::render('Companies/Index', [
            'content' => PublicContent::common(),
            'companies' => $companies,
            'filters' => [
                'keyword' => $keyword,
                'industry' => $industry,
                'location' => $location,
            ],
            'industries' => EmployerProfile::query()
                ->whereNotNull('industry')
                ->distinct()
                ->orderBy('industry')
                ->pluck('industry')
                ->filter()
                ->values(),
            'locations' => Location::query()
                ->where('is_active', true)
                ->whereIn('id', function ($query) {
                    $query->select('location_id')->from('employer_location');
                })
                ->orderBy('name')
                ->pluck('name')
                ->filter()
                ->values(),
        ]);
    }

    public function hire(WebsiteContentService $cms): Response
    {
        return Inertia::render('Platform/Hire', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('hire'),
        ]);
    }

    public function talentPool(Request $request, WebsiteContentService $cms): Response
    {
        $keyword = trim($request->string('q')->toString());
        $location = trim($request->string('location')->toString());

        $people = User::query()
            ->where('role', UserRole::Candidate)
            ->whereNotNull('username')
            ->whereHas('candidateProfile')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $like = '%'.$keyword.'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhereHas('candidateProfile', fn ($profile) => $profile->where('current_designation', 'like', $like));
                });
            })
            ->when($location !== '', function ($query) use ($location) {
                $query->whereHas('candidateProfile', fn ($profile) => $profile->where('location', 'like', '%'.$location.'%'));
            })
            ->with('candidateProfile')
            ->latest('id')
            ->limit(48)
            ->get()
            ->filter(fn (User $user) => $user->candidateProfile?->shareAllows('enabled') === true)
            ->take(24)
            ->map(function (User $user) {
                $profile = $user->candidateProfile;

                return [
                    'name' => $user->name,
                    'username' => $user->username,
                    'headline' => $profile?->shareAllows('current_role') ? $profile->current_designation : null,
                    'location' => $profile?->shareAllows('location') ? $profile->location : null,
                    'url' => route('profiles.show', $user),
                ];
            })
            ->values();

        return Inertia::render('Platform/TalentPool', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('talent-pool'),
            'people' => $people,
            'filters' => [
                'q' => $keyword,
                'location' => $location,
            ],
            'result_count' => $people->count(),
        ]);
    }

    public function staffing(WebsiteContentService $cms): Response
    {
        return Inertia::render('Platform/Staffing', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('staffing'),
        ]);
    }
}
