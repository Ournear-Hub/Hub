<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CandidateAccountNav;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidateAccountStage19Test extends TestCase
{
    public function test_registration_requires_terms_acceptance(): void
    {
        $payload = [
            'name' => 'Terms Candidate',
            'username' => 'termsstage19',
            'email' => 'terms.stage19@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'candidate',
            'accept_terms' => false,
        ];

        $this->from(route('register'))
            ->post(route('register.store'), $payload)
            ->assertSessionHasErrors('accept_terms');

        $payload['accept_terms'] = true;

        $this->post(route('register.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'terms.stage19@example.com']);
    }

    public function test_candidate_nav_is_grouped_without_public_talent_pool_link(): void
    {
        $candidate = $this->makeCandidate();
        $labels = array_column(CandidateAccountNav::items($candidate), 'label');

        $this->assertContains('Profile', $labels);
        $this->assertContains('Hire Resume', $labels);
        $this->assertContains('My Resume', $labels);
        $this->assertNotContains('Talent Pool', $labels);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('candidateNav')
                ->where('candidateNav', fn ($items) => collect($items)->pluck('label')->doesntContain('Talent Pool')));
    }

    public function test_public_profile_marks_owner_for_edit_link_only(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'stage19owner',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), ['enabled' => true])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('profiles.show', ['user' => 'stage19owner']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('viewer.is_owner', true));

        $other = $this->makeCandidate(['username' => 'stage19other', 'email_verified_at' => now()]);
        $this->actingAs($other)
            ->put(route('account.profile.privacy.update'), ['enabled' => true])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('profiles.show', ['user' => 'stage19other']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('viewer.is_owner', false));
    }

    public function test_hire_resume_preview_includes_education_and_projects_arrays(): void
    {
        $candidate = $this->makeCandidate(['name' => 'Resume Stage19']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($candidate)
            ->get(route('account.resumes.builder'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Resumes/Builder')
                ->has('preview.professional.educations')
                ->has('preview.professional.projects')
                ->missing('preview.personal.username'));
    }
}
