<?php

namespace Tests\Feature;

use App\Models\CandidateEducation;
use App\Models\CandidateProject;
use App\Support\CandidatePublicShare;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UnifiedProfileStage23FTest extends TestCase
{
    public function test_owner_sees_identity_verifications_and_can_edit_headline(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'unified-owner',
            'location' => 'Hyderabad',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('profile.username', 'unified-owner')
                ->where('profile.verifications.email', true)
                ->where('profile.verifications.whatsapp', false)
                ->where('profile.modules.identity', $candidate->business_id)
                ->where('profile.modules.candidate', $candidate->candidateProfile?->business_id)
                ->has('profile.hiring_links'));

        $this->actingAs($candidate)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => 'en',
                'professional_headline' => 'Mining Engineer | Open Cast',
                'location' => 'Hyderabad',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Mining Engineer | Open Cast', $candidate->fresh()->professional_headline);
    }

    public function test_public_profile_hides_private_education_projects_and_contact(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Private Talent',
            'username' => 'private-talent',
            'email' => 'private.talent@example.com',
            'mobile' => '9876500200',
            'location' => 'Hyderabad',
            'professional_headline' => 'Hidden headline',
            'bio' => 'Hidden bio',
        ]);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->educations()->create([
            'institution' => 'NIT Warangal',
            'qualification' => 'B.Tech',
            'field' => 'Mining',
        ]);
        $profile->projects()->create([
            'name' => 'Pit optimisation',
            'role' => 'Engineer',
            'company' => 'Apex Mines',
        ]);

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.educations', [])
                ->where('profile.projects', [])
                ->where('profile.headline', null)
                ->where('profile.location', null)
                ->where('profile.bio', null)
                ->missing('profile.email')
                ->missing('profile.mobile')
                ->missing('profile.whatsapp_number'));

        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), [
                'enabled' => true,
                'headline' => true,
                'location' => true,
                'education' => true,
                'projects' => true,
            ])
            ->assertRedirect();

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.headline', 'Hidden headline')
                ->where('profile.location', 'Hyderabad')
                ->where('profile.educations.0.institution', 'NIT Warangal')
                ->where('profile.projects.0.name', 'Pit optimisation')
                ->missing('profile.email')
                ->missing('profile.mobile'));
    }

    public function test_username_alias_redirects_and_inactive_profiles_are_hidden(): void
    {
        $candidate = $this->makeCandidate(['username' => 'alias-talent']);

        $this->get('/profile/alias-talent')
            ->assertRedirect(route('profiles.show', ['user' => 'alias-talent']));

        $candidate->forceFill(['is_active' => false])->save();
        $this->get(route('profiles.show', ['user' => 'alias-talent']))->assertNotFound();
    }

    public function test_candidate_owns_education_and_projects_and_cross_user_is_blocked(): void
    {
        $owner = $this->makeCandidate(['username' => 'edu-owner']);
        $other = $this->makeCandidate(['username' => 'edu-other']);
        $before = $owner->fresh()->profile_completion;

        $this->actingAs($owner)
            ->post(route('account.profile.educations.store'), [
                'institution' => 'Osmania University',
                'qualification' => 'M.Tech',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('account.profile.projects.store'), [
                'name' => 'Haul road study',
                'role' => 'Lead',
            ])
            ->assertRedirect();

        $education = CandidateEducation::query()->first();
        $project = CandidateProject::query()->first();
        $this->assertNotNull($education);
        $this->assertNotNull($project);
        $this->assertSame($before, $owner->fresh()->profile_completion);

        $this->actingAs($other)
            ->delete(route('account.profile.educations.destroy', $education))
            ->assertForbidden();
        $this->actingAs($other)
            ->delete(route('account.profile.projects.destroy', $project))
            ->assertForbidden();

        $this->forgetAuthentication();
        $this->post(route('account.profile.educations.store'), ['institution' => 'Guest College'])
            ->assertRedirect(route('login'));
    }

    public function test_employer_cannot_edit_candidate_profile_or_create_a_second_identity(): void
    {
        $candidate = $this->makeCandidate(['username' => 'owned-talent']);
        $employer = $this->makeEmployer(['username' => 'hiring-company']);

        $this->actingAs($employer)
            ->put(route('account.profile.update'), [
                'name' => $candidate->name,
                'username' => $candidate->username,
                'preferred_language' => 'en',
            ])
            ->assertSessionHasErrors('username');

        $this->actingAs($employer)
            ->post(route('account.profile.educations.store'), ['institution' => 'Should fail'])
            ->assertForbidden();

        $this->actingAs($employer)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.modules.company', $employer->employerProfile?->business_id)
                ->where('profile.modules.candidate', null));

        $this->assertTrue(CandidatePublicShare::defaults()['enabled']);
        $this->assertFalse(CandidatePublicShare::defaults()['education']);
        $this->assertFalse(CandidatePublicShare::defaults()['projects']);
    }

    public function test_company_public_profile_lists_open_jobs_without_private_contacts(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $company = $employer->employerProfile;
        $this->assertNotNull($company);
        $company->update([
            'company_name' => 'Unified Co',
            'contact_person' => 'Secret Person',
            'mobile_number' => '07000002222',
        ]);

        $this->get(route('companies.show', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.company_name', 'Unified Co')
                ->has('company.open_jobs')
                ->missing('company.contact_person')
                ->missing('company.mobile_number')
                ->missing('company.email'));
    }
}
