<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\Location;
use App\Services\ShowcaseContentService;
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
            ->map(function (EmployerProfile $employer) {
                $row = [
                    'id' => $employer->id,
                    'name' => $employer->publicDisplayCompany(),
                    'industry' => $employer->shareAllows('industry_size') ? $employer->displayIndustry() : null,
                    'about' => $employer->shareAllows('about') ? $employer->about_company : null,
                    'location' => $employer->shareAllows('locations')
                        ? $employer->locations->pluck('name')->filter()->implode(', ') ?: null
                        : null,
                    'logo_url' => $employer->logoUrl(false),
                    'open_jobs' => (int) $employer->open_jobs_count,
                    'url' => route('companies.show', $employer),
                    'public_jobs_url' => route('jobs.index', ['employer' => $employer->id]),
                ];

                if ($employer->ournearVerified()) {
                    $row['ournear_verified'] = true;
                }

                return $row;
            })
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

    public function hire(WebsiteContentService $cms, \App\Services\HireLandingPresentationService $hireLanding): Response
    {
        $sampleJob = Job::query()->publiclyVisible()->latest('id')->first();

        return Inertia::render('Platform/Hire', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('hire'),
            'sampleShareUrl' => $sampleJob !== null
                ? route('jobs.show', $sampleJob)
                : route('jobs.index'),
            'hirePresentation' => $hireLanding->payloadFor(request()->user()),
        ]);
    }

    public function talentPool(WebsiteContentService $cms, ShowcaseContentService $showcase): Response
    {
        return Inertia::render('Platform/TalentPool', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('talent-pool'),
            'industries' => PublicContent::industries(),
            'talentPoolShowcaseCompanies' => $showcase->publicCompaniesFor('talent_pool'),
        ]);
    }

    public function staffing(WebsiteContentService $cms, ShowcaseContentService $showcase): Response
    {
        return Inertia::render('Platform/Staffing', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('staffing'),
            'staffing_form' => [
                'industries' => PublicContent::industries(),
                'workforce_types' => ['Permanent', 'Contract', 'Temporary', 'Project teams'],
            ],
            'staffingShowcaseCompanies' => $showcase->publicCompaniesFor('staffing'),
        ]);
    }
}
