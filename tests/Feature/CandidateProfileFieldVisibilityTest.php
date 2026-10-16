<?php

namespace Tests\Feature;

use App\Models\JobCategory;
use Tests\TestCase;

class CandidateProfileFieldVisibilityTest extends TestCase
{
    public function test_profile_edit_page_omits_functional_area_and_experience_notes(): void
    {
        $candidate = $this->makeCandidate();

        $html = $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($html);
        $this->assertStringNotContainsString('Functional area', $html);
        $this->assertStringNotContainsString('Experience notes', $html);
    }

    public function test_professional_update_without_functional_area_preserves_stored_category(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $category = JobCategory::query()->first();
        $profile->update([
            'job_category_id' => $category?->id,
            'experience' => 'Legacy notes kept in database',
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Updated Role',
                'industry_id' => $profile->industry_id,
                'experience_range_id' => $profile->experience_range_id,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $profile->refresh();
        $this->assertSame('Updated Role', $profile->current_designation);
        $this->assertSame($category?->id, $profile->job_category_id);
        $this->assertSame('Legacy notes kept in database', $profile->experience);
    }

    public function test_profile_completion_uses_industry_and_experience_range_not_removed_fields(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'bio' => 'Bio',
            'mobile' => '+919999999999',
            'location' => 'City',
            'photo_path' => 'photos/test.jpg',
        ]);

        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'current_designation' => 'Engineer',
            'industry_id' => null,
            'job_category_id' => JobCategory::query()->value('id'),
            'experience' => 'Free text only',
            'experience_range_id' => null,
        ]);

        $items = collect($candidate->fresh()->profileCompletionItems())->keyBy('key');
        $this->assertFalse($items['industry_or_function']['complete']);
        $this->assertFalse($items['experience']['complete']);

        $range = \App\Models\ExperienceRange::query()->first();
        $industry = \App\Models\Industry::query()->first();
        $profile->update([
            'industry_id' => $industry?->id,
            'experience_range_id' => $range?->id,
        ]);

        $items = collect($candidate->fresh()->profileCompletionItems())->keyBy('key');
        $this->assertTrue($items['industry_or_function']['complete']);
        $this->assertTrue($items['experience']['complete']);
    }
}
