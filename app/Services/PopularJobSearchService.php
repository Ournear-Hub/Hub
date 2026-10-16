<?php

namespace App\Services;

use App\Models\Industry;
use App\Models\JobTitle;
use Illuminate\Support\Facades\Cache;

class PopularJobSearchService
{
    /**
     * Approved designation discovery for the homepage (master Job Titles — not ad-hoc listing titles).
     *
     * @return array{
     *     industries: list<array{id: int, name: string}>,
     *     designations: list<array{id: int, label: string, industry_id: int|null, href: string}>
     * }
     */
    public function homepageDiscovery(int $designationLimit = 24): array
    {
        return Cache::remember('homepage.popular_designation_discovery', now()->addHour(), function () use ($designationLimit) {
            $industries = Industry::query()
                ->where('is_active', true)
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn (Industry $industry) => [
                    'id' => (int) $industry->id,
                    'name' => (string) $industry->name,
                ])
                ->values()
                ->all();

            $designations = JobTitle::query()
                ->with('category:id,industry_id,is_active')
                ->where('is_active', true)
                ->whereHas('category', function ($query) {
                    $query->where('is_active', true)
                        ->whereHas('industry', fn ($industry) => $industry->where('is_active', true));
                })
                ->ordered()
                ->limit($designationLimit)
                ->get()
                ->unique(fn (JobTitle $title) => mb_strtolower(trim($title->name)))
                ->values()
                ->map(function (JobTitle $title) {
                    $label = trim((string) $title->name);

                    return [
                        'id' => (int) $title->id,
                        'label' => $label,
                        'industry_id' => $title->category?->industry_id ? (int) $title->category->industry_id : null,
                        'href' => route('jobs.index', ['job_title_id' => $title->id], false),
                    ];
                })
                ->filter(fn (array $row) => $row['label'] !== '')
                ->values()
                ->all();

            return [
                'industries' => $industries,
                'designations' => $designations,
            ];
        });
    }
}
