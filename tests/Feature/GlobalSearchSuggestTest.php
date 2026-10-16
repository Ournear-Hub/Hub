<?php

namespace Tests\Feature;

use Tests\TestCase;

class GlobalSearchSuggestTest extends TestCase
{
    public function test_suggest_requires_at_least_two_characters(): void
    {
        $this->getJson(route('search.suggest', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('results.jobs', [])
            ->assertJsonPath('results.companies', [])
            ->assertJsonPath('results.profiles', []);
    }

    public function test_suggest_returns_json_for_public_search(): void
    {
        $employer = $this->makeEmployer();
        $employer->employerProfile->update([
            'company_name' => 'Suggest Dropdown Co',
        ]);
        $employer->employerProfile->forceFill([
            'public_share' => ['enabled' => true, 'industry_size' => true, 'locations' => true],
        ])->save();

        $this->getJson(route('search.suggest', ['q' => 'Suggest Dropdown']))
            ->assertOk()
            ->assertJsonStructure([
                'query',
                'results' => [
                    'jobs',
                    'companies',
                    'profiles',
                ],
            ])
            ->assertJsonPath('results.jobs', [])
            ->assertJsonPath('results.companies.0.name', 'Suggest Dropdown Co');
    }

    public function test_full_search_page_still_renders(): void
    {
        $this->get(route('search', ['q' => 'test']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Search/Index'));
    }
}
