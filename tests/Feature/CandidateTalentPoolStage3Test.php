<?php

namespace Tests\Feature;

use App\Enums\LanguageProficiency;
use App\Enums\NoticePeriod;
use App\Enums\PermissionName;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserActivityType;
use App\Models\CandidateCertification;
use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;
use App\Models\SavedSearch;
use App\Models\SecurityActivity;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\UserActivity;
use App\Support\AccessCatalog;
use App\Support\CandidatePublicShare;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidateTalentPoolStage3Test extends TestCase
{
    public function test_candidate_can_store_relocation_availability_certifications_and_languages(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $before = $candidate->fresh()->profile_completion;

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'willing_to_relocate' => true,
                'availability' => 'Immediate',
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertTrue($profile->willing_to_relocate);
        $this->assertSame('Immediate', $profile->availability);

        $this->actingAs($candidate)
            ->post(route('account.profile.certifications.store'), [
                'name' => 'NEBOSH',
                'issuer' => 'NEBOSH',
                'issued_on' => '2024-01-15',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->post(route('account.profile.languages.store'), [
                'name' => 'English',
                'proficiency' => LanguageProficiency::Professional->value,
            ])
            ->assertRedirect();

        $this->assertTrue($profile->certifications()->where('name', 'NEBOSH')->exists());
        $this->assertTrue($profile->languages()->where('name', 'English')->exists());
        $this->assertSame($before, $candidate->fresh()->profile_completion);
        $this->assertFalse(in_array('certifications', array_column($candidate->fresh()->profileCompletionItems(), 'key'), true));
    }

    public function test_candidate_language_crud_persists_on_profile_and_redirects_to_languages_hash(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($candidate)
            ->post(route('account.profile.languages.store'), [
                'name' => 'Tamil',
                'proficiency' => LanguageProficiency::Conversational->value,
            ])
            ->assertRedirect(route('account.profile.edit').'#languages');

        $language = $profile->languages()->where('name', 'Tamil')->first();
        $this->assertInstanceOf(CandidateLanguage::class, $language);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.professional.languages.0.name', 'Tamil')
                ->where('profile.professional.languages.0.proficiency_label', LanguageProficiency::Conversational->label()));

        $this->actingAs($candidate)
            ->put(route('account.profile.languages.update', $language), [
                'name' => 'Tamil',
                'proficiency' => LanguageProficiency::Native->value,
            ])
            ->assertRedirect(route('account.profile.edit').'#languages');

        $this->actingAs($this->makeCandidate(['email' => 'other-lang@example.com']))
            ->put(route('account.profile.languages.update', $language), [
                'name' => 'Hacked',
                'proficiency' => LanguageProficiency::Basic->value,
            ])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.professional.languages.0.proficiency_label', LanguageProficiency::Native->label()));

        $this->actingAs($candidate)
            ->delete(route('account.profile.languages.destroy', $language))
            ->assertRedirect(route('account.profile.edit').'#languages');

        $this->assertFalse($profile->fresh()->languages()->where('name', 'Tamil')->exists());
        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('profile.professional.languages', []));
    }

    public function test_public_profile_honors_share_flags_and_never_exposes_files(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Share Candidate',
            'username' => 'share-candidate',
            'bio' => 'Private bio',
            'email' => 'share.candidate@example.com',
        ]);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'current_designation' => 'Site Engineer',
            'current_company' => 'Apex',
            'current_salary' => '50000.00',
        ]);
        $this->giveCandidateResume($candidate);
        $this->forgetAuthentication();
        $profile->certifications()->create(['name' => 'IOSH', 'issuer' => 'IOSH']);
        $profile->languages()->create([
            'name' => 'Hindi',
            'proficiency' => LanguageProficiency::Native,
        ]);

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('profile.name', 'Share Candidate')
                ->where('profile.username', 'share-candidate')
                ->where('profile.bio', null)
                ->where('profile.designation', null)
                ->where('profile.skills', [])
                ->where('profile.certifications', [])
                ->where('profile.languages', [])
                ->missing('profile.email')
                ->missing('profile.mobile')
                ->missing('profile.current_salary')
                ->missing('profile.resumes')
                ->missing('profile.documents')
                ->missing('profile.tags'));

        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), [
                'enabled' => true,
                'bio' => true,
                'current_role' => true,
                'certifications' => true,
                'languages' => true,
            ])
            ->assertRedirect();

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.bio', 'Private bio')
                ->where('profile.designation', 'Site Engineer')
                ->where('profile.current_company', 'Apex')
                ->where('profile.certifications.0.name', 'IOSH')
                ->where('profile.languages.0.name', 'Hindi')
                ->missing('profile.email')
                ->missing('profile.resumes')
                ->missing('profile.documents')
                ->missing('profile.certifications.0.candidate_document_id'));

        $this->actingAs($candidate)
            ->put(route('account.profile.privacy.update'), ['enabled' => false])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk();

        $this->forgetAuthentication();
        $this->get(route('profiles.show', ['user' => $candidate->username]))->assertNotFound();
    }

    public function test_zero_application_candidates_remain_in_talent_search_and_timeline(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Pool Only']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $this->assertSame(0, $profile->applications()->count());

        $skill = Skill::findOrCreateFromName('AutoCAD');
        $profile->skills()->sync([$skill->id]);
        $profile->update([
            'willing_to_relocate' => true,
            'availability' => '2 weeks',
            'notice_period' => NoticePeriod::Days15,
            'expected_salary' => '60000.00',
        ]);
        $profile->languages()->create([
            'name' => 'Telugu',
            'proficiency' => LanguageProficiency::Native,
        ]);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'availability' => '2 weeks',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', [
                'search' => 'Pool Only',
                'skill_id' => $skill->id,
                'willing_to_relocate' => '1',
                'language' => 'Telugu',
                'notice_period' => NoticePeriod::Days15->value,
                'expected_salary_min' => '50000',
                'candidate' => $profile->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Candidates/Index')
                ->has('candidates.data', 1)
                ->where('candidates.data.0.name', 'Pool Only')
                ->where('candidates.data.0.applications_count', 0)
                ->where('selected.availability', '2 weeks')
                ->where('selected.willing_to_relocate', true)
                ->has('activities.0.title')
                ->where('can_manage', true));

        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $candidate->id)
                ->where('type', UserActivityType::UpdatedProfile->value)
                ->exists()
        );
    }

    public function test_recruiter_can_tag_and_record_interest_without_users_manage(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $tag = Tag::query()->create(['name' => 'Hot Lead', 'slug' => 'hot-lead', 'is_active' => true]);

        $this->assertFalse($recruiter->hasPermission(PermissionName::UsersManage->value));
        $this->assertTrue($recruiter->hasPermission(PermissionName::CandidatesManage->value));

        $this->actingAs($viewer)
            ->put(route('admin.candidates.tags.sync', $profile), ['tag_ids' => [$tag->id]])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->put(route('admin.users.tags', $candidate), ['tag_ids' => [$tag->id]])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->put(route('admin.candidates.tags.sync', $profile), ['tag_ids' => [$tag->id]])
            ->assertRedirect();
        $this->assertTrue($candidate->fresh()->tags->contains('id', $tag->id));

        $this->actingAs($recruiter)
            ->get(route('admin.candidates.index', ['tag_id' => $tag->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('candidates.data', 1)
                ->where('candidates.data.0.id', $profile->id));

        $this->actingAs($recruiter)
            ->post(route('admin.candidates.interest.store', $profile), [
                'note' => 'Called about a Hyderabad opening.',
            ])
            ->assertRedirect();

        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $candidate->id)
                ->where('type', UserActivityType::TalentInterest->value)
                ->where('description', 'Called about a Hyderabad opening.')
                ->exists()
        );
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::TalentInterestRecorded->value)
                ->where('actor_id', $recruiter->id)
                ->exists()
        );
    }

    public function test_staff_can_save_and_reuse_talent_searches(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $other = $this->makeStaff(StaffRoleSlug::Viewer->value);

        $this->actingAs($recruiter)
            ->post(route('admin.candidates.saved-searches.store'), [
                'name' => 'Immediate CAD',
                'filters' => ['willing_to_relocate' => '1', 'language' => 'English'],
            ])
            ->assertRedirect();

        $search = SavedSearch::query()->where('user_id', $recruiter->id)->where('name', 'Immediate CAD')->first();
        $this->assertNotNull($search);
        $this->assertSame('1', $search->filters['willing_to_relocate'] ?? null);

        $this->actingAs($recruiter)
            ->post(route('admin.candidates.saved-searches.store'), [
                'name' => 'Immediate CAD',
                'filters' => ['language' => 'Hindi'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('name');

        $this->actingAs($other)
            ->delete(route('admin.candidates.saved-searches.destroy', $search))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->get(route('admin.candidates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('saved_searches', 1)
                ->where('saved_searches.0.name', 'Immediate CAD'));

        $this->actingAs($recruiter)
            ->delete(route('admin.candidates.saved-searches.destroy', $search))
            ->assertRedirect();
        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_employer_cannot_access_the_talent_pool(): void
    {
        $employer = $this->makeEmployer();
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)->get(route('admin.candidates.index'))->assertForbidden();
        $this->actingAs($employer)
            ->put(route('admin.candidates.tags.sync', $profile), ['tag_ids' => []])
            ->assertForbidden();
        $this->actingAs($employer)
            ->post(route('admin.candidates.saved-searches.store'), [
                'name' => 'Employer search',
                'filters' => ['search' => 'x'],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_add_certifications_and_languages_on_a_candidate(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($admin)
            ->post(route('admin.candidates.certifications.store', $profile), [
                'name' => 'PMP',
                'issuer' => 'PMI',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.candidates.languages.store', $profile), [
                'name' => 'Arabic',
                'proficiency' => LanguageProficiency::Conversational->value,
            ])
            ->assertRedirect();

        $this->assertInstanceOf(CandidateCertification::class, $profile->certifications()->where('name', 'PMP')->first());
        $this->assertInstanceOf(CandidateLanguage::class, $profile->languages()->where('name', 'Arabic')->first());
    }

    public function test_stage_three_does_not_add_permissions_or_a_candidates_table(): void
    {
        $this->assertSame(34, count(AccessCatalog::permissionNames()));
        $this->assertContains(PermissionName::CandidatesView->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::CandidatesManage->value, AccessCatalog::permissionNames());
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('candidates'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('candidate_profiles'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('candidate_profiles', 'willing_to_relocate'));
        $this->assertSame(CandidatePublicShare::defaults()['enabled'], true);
        $this->assertFalse(CandidatePublicShare::defaults()['current_role']);
    }
}
