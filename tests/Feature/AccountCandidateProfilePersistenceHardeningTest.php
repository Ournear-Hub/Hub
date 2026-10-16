<?php

namespace Tests\Feature;

use App\Enums\PreferredLanguage;
use App\Models\CandidateResume;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * REG-CAN-PROF-001 — candidate profile partial-update persistence and view parity.
 */
class AccountCandidateProfilePersistenceHardeningTest extends TestCase
{
    public function test_personal_fields_persist_after_basic_profile_update(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Before Name',
            'username' => 'before-user',
            'bio' => 'Existing bio',
            'professional_headline' => 'Existing headline',
            'location' => 'Chennai',
        ]);
        $candidate->candidateProfile?->update(['mobile' => '+919900000001']);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => 'After Name',
                'username' => 'after-user',
                'mobile' => '9900000002',
                'location' => 'Hyderabad',
                'bio' => 'Updated bio text',
                'professional_headline' => 'Updated headline',
                'preferred_language' => PreferredLanguage::English->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $candidate->refresh();
        $this->assertSame('After Name', $candidate->name);
        $this->assertSame('after-user', $candidate->username);
        $this->assertSame('Updated bio text', $candidate->bio);
        $this->assertSame('Updated headline', $candidate->professional_headline);
        $this->assertSame('Hyderabad', $candidate->location);
        $this->assertSame('+919900000002', $candidate->mobile);
    }

    public function test_professional_fields_persist_and_reappear_on_profile_edit(): void
    {
        $candidate = $this->makeCandidate(['username' => 'pro-reload']);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Planner',
                'current_company' => 'Infra Co',
                'experience' => '8+ years',
                'qualification' => 'M.Tech',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.professional.current_designation', 'Planner')
                ->where('profile.professional.current_company', 'Infra Co')
                ->where('profile.professional.experience', '8+ years')
                ->where('profile.professional.qualification', 'M.Tech'));
    }

    public function test_basic_profile_update_without_legacy_fields_preserves_candidate_profile_strings(): void
    {
        $candidate = $this->makeCandidate(['username' => 'legacy-keep']);
        $candidate->candidateProfile?->update([
            'experience' => 'Stored experience',
            'qualification' => 'Stored qualification',
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => PreferredLanguage::English->value,
                'bio' => 'Only bio changed',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $profile = $candidate->fresh()->candidateProfile;
        $this->assertSame('Stored experience', $profile?->experience);
        $this->assertSame('Stored qualification', $profile?->qualification);
        $this->assertSame('Only bio changed', $candidate->fresh()->bio);
    }

    public function test_basic_profile_update_without_optional_personal_fields_preserves_them(): void
    {
        $candidate = $this->makeCandidate([
            'bio' => 'Keep this bio',
            'professional_headline' => 'Keep headline',
            'location' => 'Pune',
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => 'Renamed Only',
                'username' => $candidate->username,
                'preferred_language' => PreferredLanguage::English->value,
            ])
            ->assertRedirect();

        $candidate->refresh();
        $this->assertSame('Renamed Only', $candidate->name);
        $this->assertSame('Keep this bio', $candidate->bio);
        $this->assertSame('Keep headline', $candidate->professional_headline);
        $this->assertSame('Pune', $candidate->location);
    }

    public function test_invalid_basic_profile_input_returns_field_errors(): void
    {
        $candidate = $this->makeCandidate(['username' => 'valid-name']);

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.update'), [
                'name' => '',
                'username' => 'bad name!',
                'preferred_language' => 'not-a-language',
            ])
            ->assertSessionHasErrors(['name', 'username', 'preferred_language']);
    }

    public function test_incomplete_candidate_profile_page_renders_without_required_optional_values(): void
    {
        $candidate = $this->makeCandidate([
            'bio' => null,
            'professional_headline' => null,
            'location' => null,
            'photo_path' => null,
        ]);
        $candidate->candidateProfile?->update([
            'current_designation' => null,
            'current_company' => null,
            'experience' => null,
            'qualification' => null,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('profile.bio', null)
                ->where('profile.professional.current_designation', null)
                ->has('profile.completion_items')
                ->where('profile.profile_completion', $candidate->fresh()->calculateProfileCompletion()));
    }

    public function test_profile_completion_reflects_persisted_required_scoring_fields(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Score Check',
            'username' => 'score-check',
            'email_verified_at' => now(),
            'mobile' => '+919988776655',
            'location' => 'Delhi',
            'bio' => 'Short bio',
        ]);

        $before = $candidate->fresh()->calculateProfileCompletion();

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Engineer',
            ])
            ->assertRedirect();

        $after = $candidate->fresh()->calculateProfileCompletion();
        $this->assertGreaterThan($before, $after);
        $this->assertLessThanOrEqual(100, $after);
    }

    public function test_candidate_cannot_update_another_candidates_profile_via_spoofed_ids(): void
    {
        $owner = $this->makeCandidate(['name' => 'Owner', 'username' => 'owner-persist']);
        $intruder = $this->makeCandidate(['name' => 'Intruder', 'username' => 'intruder-persist']);

        $this->actingAs($intruder)
            ->put(route('account.profile.update'), [
                'name' => 'Hijacked',
                'username' => 'hijacked-user',
                'preferred_language' => PreferredLanguage::English->value,
                'user_id' => $owner->id,
                'id' => $owner->id,
            ])
            ->assertRedirect();

        $this->assertSame('Owner', $owner->fresh()->name);
        $this->assertSame('owner-persist', $owner->fresh()->username);
        $this->assertSame('Hijacked', $intruder->fresh()->name);
    }

    public function test_library_resume_reference_remains_on_profile_after_personal_update(): void
    {
        $candidate = $this->makeCandidate(['username' => 'resume-ref']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        CandidateResume::factory()->create([
            'candidate_profile_id' => $profile->id,
            'title' => 'Primary CV',
            'is_primary' => true,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.library_resume.has_upload', true)
                ->where('profile.library_resume.title', 'Primary CV'));

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => 'Resume Owner',
                'username' => $candidate->username,
                'preferred_language' => PreferredLanguage::English->value,
                'location' => 'Kolkata',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.library_resume.has_upload', true)
                ->where('profile.library_resume.title', 'Primary CV')
                ->where('profile.location', 'Kolkata'));
    }

    public function test_explicit_clear_of_submitted_optional_field_still_persists_null(): void
    {
        $candidate = $this->makeCandidate(['bio' => 'Will clear']);

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => PreferredLanguage::English->value,
                'bio' => '',
            ])
            ->assertRedirect();

        $this->assertNull($candidate->fresh()->bio);
    }
}
