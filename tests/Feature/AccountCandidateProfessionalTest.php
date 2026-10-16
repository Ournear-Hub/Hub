<?php

namespace Tests\Feature;

use App\Enums\NoticePeriod;
use App\Enums\StaffRoleSlug;
use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateProfile;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountCandidateProfessionalTest extends TestCase
{
    public function test_candidate_can_update_current_professional_profile(): void
    {
        $candidate = $this->makeCandidate(['username' => 'pro-owner']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'experience' => '5 years on site',
            'qualification' => 'B.Tech',
            'mobile' => '9000000001',
            'location' => 'Hyderabad',
        ]);

        $category = JobCategory::query()->first();
        $industry = $category?->industry ?? Industry::query()->first();
        $range = ExperienceRange::query()->first();
        $qualification = Qualification::query()->first();

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Site Engineer',
                'current_company' => 'Apex Civil Works',
                'industry_id' => $industry?->id,
                'job_category_id' => $category?->id,
                'current_salary' => '45000.00',
                'expected_salary' => '55000.00',
                'notice_period' => NoticePeriod::Days30->value,
                'experience' => '5 years on site',
                'experience_range_id' => $range?->id,
                'qualification' => 'B.Tech',
                'qualification_id' => $qualification?->id,
                'user_id' => 999999,
                'candidate_profile_id' => 999999,
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame('Site Engineer', $profile->current_designation);
        $this->assertSame('Apex Civil Works', $profile->current_company);
        $this->assertSame($industry?->id, $profile->industry_id);
        $this->assertSame($category?->id, $profile->job_category_id);
        $this->assertSame('45000.00', $profile->current_salary);
        $this->assertSame('55000.00', $profile->expected_salary);
        $this->assertSame(NoticePeriod::Days30, $profile->notice_period);
        $this->assertSame('5 years on site', $profile->experience);
        $expectedQualification = $qualification?->name ?? 'B.Tech';
        $this->assertSame($expectedQualification, $profile->qualification);
        $this->assertSame($qualification?->id, $profile->qualification_id);
        $this->assertSame($range?->id, $profile->experience_range_id);
        $this->assertSame('9000000001', $profile->mobile);
        $this->assertSame('Hyderabad', $profile->location);
        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $profile->business_id);
        $this->assertFalse(Schema::hasColumn('users', 'current_company'));
        $this->assertNull($candidate->fresh()->getAttribute('current_company'));
    }

    public function test_existing_experience_is_preserved_when_other_professional_fields_change(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update(['experience' => '3-5 years', 'qualification' => 'NVQ']);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Foreman',
                'current_company' => 'North Build',
                'experience' => '3-5 years',
                'qualification' => 'NVQ',
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame('3-5 years', $profile->experience);
        $this->assertSame('NVQ', $profile->qualification);
        $this->assertSame('Foreman', $profile->current_designation);
    }

    public function test_professional_salary_and_notice_period_are_validated(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.professional.update'), [
                'current_salary' => '-10',
                'expected_salary' => '-1',
                'notice_period' => 'next-week',
                'industry_id' => 999999,
                'job_category_id' => 999999,
            ])
            ->assertSessionHasErrors([
                'current_salary',
                'expected_salary',
                'notice_period',
                'industry_id',
                'job_category_id',
            ]);
    }

    public function test_candidate_can_add_and_remove_skills_without_duplicates(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($candidate)
            ->post(route('account.profile.skills.store'), ['name' => 'AutoCAD'])
            ->assertRedirect();

        $this->assertTrue($profile->skills()->where('normalized_name', 'autocad')->exists());

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->post(route('account.profile.skills.store'), ['name' => 'autocad'])
            ->assertSessionHasErrors('name');

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->post(route('account.profile.skills.store'), ['name' => '   '])
            ->assertSessionHasErrors('name');

        $skill = $profile->skills()->first();
        $this->assertNotNull($skill);

        $this->actingAs($candidate)
            ->delete(route('account.profile.skills.destroy', $skill))
            ->assertRedirect();

        $this->assertFalse($profile->fresh()->skills()->whereKey($skill->id)->exists());
    }

    public function test_candidate_cannot_modify_another_candidates_skills(): void
    {
        $owner = $this->makeCandidate(['username' => 'skill-owner']);
        $intruder = $this->makeCandidate(['username' => 'skill-intruder']);
        $ownerProfile = $owner->candidateProfile;
        $intruderProfile = $intruder->candidateProfile;
        $this->assertNotNull($ownerProfile);
        $this->assertNotNull($intruderProfile);

        $this->actingAs($owner)
            ->post(route('account.profile.skills.store'), ['name' => 'STAAD'])
            ->assertRedirect();

        $skill = $ownerProfile->skills()->first();
        $this->assertNotNull($skill);

        $this->actingAs($intruder)
            ->post(route('account.profile.skills.store'), [
                'name' => 'Revit',
                'candidate_profile_id' => $ownerProfile->id,
            ])
            ->assertRedirect();

        $this->assertTrue($intruderProfile->fresh()->skills()->where('normalized_name', 'revit')->exists());
        $this->assertFalse($ownerProfile->fresh()->skills()->where('normalized_name', 'revit')->exists());

        $this->actingAs($intruder)
            ->delete(route('account.profile.skills.destroy', $skill))
            ->assertNotFound();

        $this->assertTrue($ownerProfile->fresh()->skills()->whereKey($skill->id)->exists());
    }

    public function test_candidate_can_manage_multiple_employment_history_records(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $industry = Industry::query()->first();

        $this->actingAs($candidate)
            ->post(route('account.profile.employment-history.store'), [
                'company' => 'First Contractor',
                'designation' => 'Junior Engineer',
                'industry_id' => $industry?->id,
                'started_on' => '2018-01-01',
                'ended_on' => '2020-12-31',
                'is_current' => false,
                'description' => 'Site support',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->post(route('account.profile.employment-history.store'), [
                'company' => 'Current Contractor',
                'designation' => 'Site Engineer',
                'started_on' => '2021-01-01',
                'is_current' => true,
                'description' => 'Live project',
            ])
            ->assertRedirect();

        $this->assertSame(2, $profile->employmentHistories()->count());
        $current = $profile->employmentHistories()->where('is_current', true)->first();
        $this->assertNotNull($current);
        $this->assertNull($current->ended_on);
        $this->assertSame(1, $profile->employmentHistories()->where('is_current', true)->count());

        $this->actingAs($candidate)
            ->put(route('account.profile.employment-history.update', $current), [
                'company' => 'Current Contractor Ltd',
                'designation' => 'Senior Site Engineer',
                'started_on' => '2021-01-01',
                'is_current' => true,
                'description' => 'Updated role',
            ])
            ->assertRedirect();

        $current->refresh();
        $this->assertSame('Current Contractor Ltd', $current->company);
        $this->assertSame('Senior Site Engineer', $current->designation);

        $previous = $profile->employmentHistories()->where('is_current', false)->first();
        $this->assertNotNull($previous);

        $this->actingAs($candidate)
            ->delete(route('account.profile.employment-history.destroy', $previous))
            ->assertRedirect();

        $this->assertSame(1, $profile->fresh()->employmentHistories()->count());
    }

    public function test_employment_history_dates_and_current_flag_are_validated(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->post(route('account.profile.employment-history.store'), [
                'company' => 'Broken Dates Ltd',
                'designation' => 'Engineer',
                'started_on' => '2022-06-01',
                'ended_on' => '2021-01-01',
                'is_current' => false,
            ])
            ->assertSessionHasErrors('ended_on');

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->post(route('account.profile.employment-history.store'), [
                'company' => 'No End Date Ltd',
                'designation' => 'Engineer',
                'started_on' => '2022-06-01',
                'is_current' => false,
            ])
            ->assertSessionHasErrors('ended_on');
    }

    public function test_candidate_cannot_modify_another_candidates_employment_history(): void
    {
        $owner = $this->makeCandidate(['username' => 'history-owner']);
        $intruder = $this->makeCandidate(['username' => 'history-intruder']);
        $history = CandidateEmploymentHistory::query()->create([
            'candidate_profile_id' => $owner->candidateProfile?->id,
            'company' => 'Owner Co',
            'designation' => 'Owner Role',
            'started_on' => '2020-01-01',
            'ended_on' => '2021-01-01',
            'is_current' => false,
        ]);

        $this->actingAs($intruder)
            ->put(route('account.profile.employment-history.update', $history), [
                'company' => 'Hijacked Co',
                'designation' => 'Hijacked Role',
                'started_on' => '2020-01-01',
                'ended_on' => '2021-01-01',
                'is_current' => false,
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('account.profile.employment-history.destroy', $history))
            ->assertForbidden();

        $history->refresh();
        $this->assertSame('Owner Co', $history->company);
        $this->assertSame($owner->candidateProfile?->id, $history->candidate_profile_id);
    }

    public function test_professional_only_update_does_not_clear_existing_preferences(): void
    {
        $candidate = $this->makeCandidate(['username' => 'pro-partial-pref']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $hyderabad = Location::query()->firstOrCreate(
            ['name' => 'Hyderabad'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $profile->update([
            'current_designation' => 'Foreman',
            'employment_preference' => 'Site roles only',
            'willing_to_relocate' => true,
            'availability' => '30 days',
        ]);
        $profile->preferredLocations()->sync([$hyderabad->id]);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Senior Foreman',
                'current_company' => 'North Build',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $profile->refresh();
        $this->assertSame('Senior Foreman', $profile->current_designation);
        $this->assertSame('North Build', $profile->current_company);
        $this->assertSame('Site roles only', $profile->employment_preference);
        $this->assertTrue($profile->willing_to_relocate);
        $this->assertSame('30 days', $profile->availability);
        $this->assertEqualsCanonicalizing([$hyderabad->id], $profile->preferredLocations()->pluck('locations.id')->all());
    }

    public function test_preferences_only_update_does_not_clear_existing_professional_fields(): void
    {
        $candidate = $this->makeCandidate(['username' => 'pref-partial']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'current_designation' => 'Site Engineer',
            'current_company' => 'Build Co',
        ]);

        $hyderabad = Location::query()->firstOrCreate(
            ['name' => 'Hyderabad'],
            ['is_active' => true, 'sort_order' => 1],
        );

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'preferred_location_ids' => [$hyderabad->id],
                'employment_preference' => 'Open to site work',
                'willing_to_relocate' => true,
                'availability' => 'Immediate',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $profile->refresh();
        $this->assertSame('Site Engineer', $profile->current_designation);
        $this->assertSame('Build Co', $profile->current_company);
        $this->assertSame('Open to site work', $profile->employment_preference);
        $this->assertTrue($profile->willing_to_relocate);
        $this->assertEqualsCanonicalizing([$hyderabad->id], $profile->preferredLocations()->pluck('locations.id')->all());

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.professional.current_designation', 'Site Engineer')
                ->where('profile.professional.employment_preference', 'Open to site work')
                ->where('profile.professional.willing_to_relocate', true));
    }

    public function test_legacy_full_form_empty_strings_clear_unsubmitted_professional_fields(): void
    {
        $candidate = $this->makeCandidate(['username' => 'pref-legacy-payload']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update(['current_designation' => 'Planner']);

        $hyderabad = Location::query()->firstOrCreate(
            ['name' => 'Hyderabad'],
            ['is_active' => true, 'sort_order' => 1],
        );

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => '',
                'current_company' => '',
                'industry_id' => '',
                'job_category_id' => '',
                'experience' => '',
                'experience_range_id' => '',
                'qualification' => '',
                'qualification_id' => '',
                'current_salary' => '',
                'expected_salary' => '',
                'notice_period' => '',
                'preferred_location_ids' => [$hyderabad->id],
                'preferred_employment_type_ids' => [],
                'employment_preference' => 'Night shift OK',
                'willing_to_relocate' => false,
                'availability' => '',
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame('Night shift OK', $profile->employment_preference);
        $this->assertEqualsCanonicalizing([$hyderabad->id], $profile->preferredLocations()->pluck('locations.id')->all());
        // Documents current backend behavior when empty strings are submitted for untouched professional fields.
        $this->assertNull($profile->current_designation);
    }

    public function test_candidate_can_set_and_remove_employment_preferences(): void
    {
        $candidate = $this->makeCandidate();
        $hyderabad = Location::query()->firstOrCreate(
            ['name' => 'Hyderabad'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $mumbai = Location::query()->firstOrCreate(
            ['name' => 'Mumbai'],
            ['is_active' => true, 'sort_order' => 2],
        );
        $fullTime = EmploymentType::findByName('Full-time');
        $contract = EmploymentType::findByName('Contract');

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'preferred_location_ids' => [$hyderabad->id, $mumbai->id],
                'preferred_employment_type_ids' => array_values(array_filter([$fullTime?->id, $contract?->id])),
                'employment_preference' => 'Open to night shifts',
            ])
            ->assertRedirect();

        $profile = $candidate->fresh()->candidateProfile;
        $this->assertNotNull($profile);
        $this->assertEqualsCanonicalizing(
            [$hyderabad->id, $mumbai->id],
            $profile->preferredLocations()->pluck('locations.id')->all()
        );
        $this->assertSame('Open to night shifts', $profile->employment_preference);
        $this->assertGreaterThanOrEqual(1, $profile->preferredEmploymentTypes()->count());

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'preferred_location_ids' => [$hyderabad->id],
                'preferred_employment_type_ids' => [],
                'employment_preference' => '',
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertEqualsCanonicalizing([$hyderabad->id], $profile->preferredLocations()->pluck('locations.id')->all());
        $this->assertSame(0, $profile->preferredEmploymentTypes()->count());
        $this->assertNull($profile->employment_preference);

        $this->actingAs($candidate)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.professional.update'), [
                'preferred_location_ids' => [999999],
                'preferred_employment_type_ids' => [999999],
            ])
            ->assertSessionHasErrors(['preferred_location_ids.0', 'preferred_employment_type_ids.0']);
    }

    public function test_employer_cannot_modify_candidate_professional_profile(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)
            ->put(route('account.profile.professional.update'), [
                'current_company' => 'Should Fail Ltd',
            ])
            ->assertForbidden();

        $this->actingAs($employer)
            ->post(route('account.profile.skills.store'), ['name' => 'Hacking'])
            ->assertForbidden();

        $this->actingAs($employer)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->missing('profile.professional'));

        $this->assertNull($profile->fresh()->current_company);
    }

    public function test_candidate_cannot_modify_another_candidate_professional_profile(): void
    {
        $owner = $this->makeCandidate(['username' => 'pro-target']);
        $intruder = $this->makeCandidate(['username' => 'pro-intruder']);
        $owner->candidateProfile?->update(['current_company' => 'Owner Company']);

        $this->actingAs($intruder)
            ->put(route('account.profile.professional.update'), [
                'current_company' => 'Intruder Company',
                'id' => $owner->candidateProfile?->id,
                'candidate_profile_id' => $owner->candidateProfile?->id,
                'user_id' => $owner->id,
            ])
            ->assertRedirect();

        $this->assertSame('Owner Company', $owner->fresh()->candidateProfile?->current_company);
        $this->assertSame('Intruder Company', $intruder->fresh()->candidateProfile?->current_company);
        $this->assertNotSame($owner->id, $intruder->id);
        $this->assertTrue($owner->isCandidate());
        $this->assertTrue($intruder->isCandidate());
        $this->assertFalse($owner->isAdmin());
    }

    public function test_admin_candidate_workspace_shows_professional_data_and_staff_can_update(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Asha Professional']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'QS',
                'current_company' => 'BuildCorp',
                'current_salary' => '60000.00',
                'expected_salary' => '70000.00',
                'notice_period' => NoticePeriod::Days15->value,
            ])
            ->assertRedirect();
        $this->actingAs($candidate)
            ->post(route('account.profile.skills.store'), ['name' => 'Billing']);

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', ['candidate' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Candidates/Index')
                ->where('selected.current_company', 'BuildCorp')
                ->where('selected.current_designation', 'QS')
                ->where('selected.present_position', 'QS')
                ->where('selected.current_salary', '60000.00')
                ->where('selected.expected_salary', '70000.00')
                ->where('selected.notice_period', '15 days')
                ->where('selected.skills', 'Billing'));

        $this->actingAs($admin)
            ->from(route('admin.candidates.index', ['candidate' => $profile->id]))
            ->put(route('admin.candidates.update', $profile), [
                'name' => 'Asha Professional',
                'mobile' => '07111111111',
                'current_company' => 'BuildCorp UK',
                'current_designation' => 'Senior QS',
            ])
            ->assertRedirect();

        $this->assertSame('BuildCorp UK', $profile->fresh()->current_company);
        $this->assertSame('Senior QS', $profile->fresh()->current_designation);

        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $this->actingAs($viewer)
            ->get(route('admin.candidates.index', ['candidate' => $profile->id]))
            ->assertOk();
        $this->actingAs($viewer)
            ->put(route('admin.candidates.update', $profile), [
                'name' => 'Blocked Name',
                'current_company' => 'Should Not Save',
            ])
            ->assertForbidden();
    }

    public function test_professional_fields_affect_completion_without_optional_penalty(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Score Pro',
            'email' => 'score.pro@example.com',
            'username' => 'score-pro',
            'email_verified_at' => null,
            'mobile' => null,
            'location' => null,
            'bio' => null,
            'photo_path' => null,
        ]);

        $this->assertSame(40, $candidate->fresh()->calculateProfileCompletion());

        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update(['current_designation' => 'Engineer']);
        app(\App\Services\CandidateProfessionalProfileService::class)->refreshCompletion($profile);
        $this->assertSame(42, $candidate->fresh()->profile_completion);

        $range = ExperienceRange::query()->first();
        $profile->update(['experience_range_id' => $range?->id]);
        app(\App\Services\CandidateProfessionalProfileService::class)->refreshCompletion($profile->fresh());
        $this->assertSame(44, $candidate->fresh()->profile_completion);

        $industry = Industry::query()->first();
        $profile->update(['industry_id' => $industry?->id]);
        app(\App\Services\CandidateProfessionalProfileService::class)->refreshCompletion($profile->fresh());
        $this->assertSame(46, $candidate->fresh()->profile_completion);

        $candidate->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($candidate)->post(route('account.profile.skills.store'), ['name' => 'Excel']);
        $this->assertSame(48, $candidate->fresh()->profile_completion);

        $this->actingAs($candidate)->post(route('account.profile.employment-history.store'), [
            'company' => 'Past Co',
            'designation' => 'Assistant',
            'started_on' => '2019-01-01',
            'ended_on' => '2020-01-01',
            'is_current' => false,
        ]);
        $this->assertSame(50, $candidate->fresh()->profile_completion);

        $beforeOptional = $candidate->fresh()->profile_completion;
        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_salary' => '10000.00',
                'expected_salary' => '12000.00',
                'notice_period' => NoticePeriod::Immediate->value,
                'employment_preference' => 'Day shift only',
            ])
            ->assertRedirect();
        $this->assertSame($beforeOptional, $candidate->fresh()->profile_completion);
        $this->assertLessThanOrEqual(100, $candidate->fresh()->profile_completion);
    }

    public function test_account_profile_page_exposes_professional_sections_for_candidates(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->has('profile.professional')
                ->has('profile.completion_items')
                ->has('lookups.industries')
                ->has('lookups.notice_periods')
                ->has('lookups.employment_types'));
    }

    public function test_professional_update_persists_on_profile_edit_and_updates_completion_items(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Persist Pro',
            'email' => 'persist.pro@example.com',
            'username' => 'persist-pro',
            'email_verified_at' => null,
            'mobile' => null,
            'location' => null,
            'bio' => null,
            'photo_path' => null,
        ]);

        $candidate->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_designation' => 'Surveyor',
                'current_company' => 'Site Ltd',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.professional.current_designation', 'Surveyor')
                ->where('profile.professional.current_company', 'Site Ltd')
                ->where('profile.profile_completion', $candidate->fresh()->calculateProfileCompletion())
                ->where('profile.completion_items', function ($items): bool {
                    $map = collect($items)->keyBy('key');

                    return ($map['current_role']['complete'] ?? false) === true;
                }));
    }

    public function test_guest_job_application_still_works_after_professional_profile(): void
    {
        $job = Job::factory()->create();

        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('applications', [
            'email' => 'v12b.guest@example.com',
        ]);
    }

    public function test_user_remains_separate_from_candidate_and_existing_ids_are_preserved(): void
    {
        $candidate = $this->makeCandidate(['username' => 'identity-split']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $businessId = $profile->business_id;

        $this->actingAs($candidate)
            ->put(route('account.profile.professional.update'), [
                'current_company' => 'Separate Co',
            ])
            ->assertRedirect();

        $candidate->refresh();
        $profile->refresh();
        $this->assertSame($businessId, $profile->business_id);
        $this->assertTrue($candidate->isCandidate());
        $this->assertInstanceOf(User::class, $candidate);
        $this->assertInstanceOf(CandidateProfile::class, $profile);
        $this->assertSame($candidate->id, $profile->user_id);
        $this->assertSame($candidate->id, $profile->user()->value('id'));
        $this->assertArrayNotHasKey('current_company', $candidate->getAttributes());
        $this->assertArrayNotHasKey('current_salary', $candidate->getAttributes());
        $this->assertFalse(Schema::hasColumn('users', 'skills'));
        $this->assertFalse(Schema::hasColumn('users', 'current_salary'));
    }
}
