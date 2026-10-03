<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserActivityType;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\SecurityActivity;
use App\Models\Tag;
use App\Models\UserActivity;
use App\Support\AccessCatalog;
use App\Support\EmployerPublicShare;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerCompanyFoundationStage4Test extends TestCase
{
    public function test_employer_can_store_locations_benefits_and_socials(): void
    {
        $employer = $this->eligibleEmployer();
        $location = $this->location();

        $this->actingAs($employer)
            ->put(route('account.profile.update'), [
                'name' => $employer->name,
                'username' => $employer->username,
                'preferred_language' => 'en',
                'company_name' => 'Harbor Works',
                'location_ids' => [$location->id],
                'benefits' => ['Health cover', 'Remote Fridays'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/harbor-works',
                ],
            ])
            ->assertRedirect();

        $profile = $employer->fresh()->employerProfile;
        $this->assertNotNull($profile);
        $this->assertSame('Harbor Works', $profile->company_name);
        $this->assertTrue($profile->locations->contains('id', $location->id));
        $this->assertContains('Health cover', $profile->benefitList());
        $this->assertSame('https://www.linkedin.com/company/harbor-works', $profile->socialLinks()['linkedin']);
    }

    public function test_public_company_honors_share_flags_and_never_exposes_private_fields(): void
    {
        $employer = $this->eligibleEmployer();
        $location = $this->location();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'company_name' => 'Share Company',
            'about_company' => 'Public about',
            'company_details' => 'Public details',
            'company_website' => 'https://share.example',
            'company_size' => '51-200',
            'industry' => 'Construction',
            'contact_person' => 'Secret Contact',
            'mobile_number' => '07000000000',
            'benefits' => ['Gym'],
        ]);
        $profile->locations()->sync([$location->id]);

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.company_name', 'Share Company')
                ->where('company.company_website', 'https://share.example')
                ->where('company.about_company', 'Public about')
                ->where('company.ournear_verified', false)
                ->where('company.locations', [])
                ->where('company.benefits', [])
                ->missing('company.contact_person')
                ->missing('company.mobile_number')
                ->missing('company.email')
                ->missing('company.documents')
                ->missing('company.tags'));

        $this->actingAs($employer)
            ->put(route('account.profile.company-privacy.update'), [
                'enabled' => true,
                'locations' => true,
                'benefits' => true,
                'website' => false,
            ])
            ->assertRedirect();

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('company.company_website', null)
                ->where('company.locations.0', $location->name)
                ->where('company.benefits.0', 'Gym')
                ->missing('company.contact_person')
                ->missing('company.documents'));

        $this->actingAs($employer)
            ->put(route('account.profile.company-privacy.update'), ['enabled' => false])
            ->assertRedirect();

        $this->get(route('companies.show', $profile))->assertNotFound();
    }

    public function test_public_company_shows_ourneer_badge_only_when_approved(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Badge Co']);

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('company.ournear_verified', false));

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->patch(route('admin.employers.posting-approval', $profile))
            ->assertRedirect();

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('company.ournear_verified', true));
    }

    public function test_posting_restriction_blocks_eligible_and_approved_employers(): void
    {
        $employer = $this->eligibleEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.employers.posting-restriction', $profile), ['restricted' => true])
            ->assertRedirect();

        $this->assertNotNull($profile->fresh()->posting_restricted_at);
        $this->assertTrue(
            SecurityActivity::query()
                ->where('action', SecurityActivityType::EmployerPostingRestricted->value)
                ->where('subject_id', $profile->id)
                ->exists()
        );

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload())
            ->assertSessionHasErrors('posting');
        $this->assertSame(0, Job::query()->count());

        $unverified = $this->makeEmployer([
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
        $unverifiedProfile = $unverified->employerProfile;
        $this->assertNotNull($unverifiedProfile);

        $this->actingAs($admin)
            ->patch(route('admin.employers.posting-approval', $unverifiedProfile))
            ->assertRedirect();
        $this->actingAs($admin)
            ->patch(route('admin.employers.posting-restriction', $unverifiedProfile), ['restricted' => true])
            ->assertRedirect();

        $this->actingAs($unverified)
            ->post(route('account.employer.jobs.store'), $this->jobPayload(['title' => 'Approved But Restricted']))
            ->assertSessionHasErrors('posting');

        $this->actingAs($admin)
            ->patch(route('admin.employers.posting-restriction', $profile), ['restricted' => false])
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.store'), $this->jobPayload())
            ->assertRedirect(route('account.employer.jobs.index'));
        $this->assertSame(1, Job::query()->count());
        $this->assertSame('trial', Job::query()->first()?->posting_tier);
    }

    public function test_zero_job_employers_remain_in_search_and_timeline(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer(['name' => 'Pool Employer']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'Zero Job Co']);
        $this->assertSame(0, $profile->jobs()->count());

        $this->actingAs($employer)
            ->put(route('account.profile.update'), [
                'name' => $employer->name,
                'username' => $employer->username,
                'preferred_language' => 'en',
                'company_name' => 'Zero Job Co',
                'about_company' => 'Still searching for first hire.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.employers.index', [
                'search' => 'Zero Job Co',
                'restricted' => '0',
                'ournear_verified' => '0',
                'employer' => $profile->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employers/Index')
                ->has('employers.data', 1)
                ->where('employers.data.0.company_name', 'Zero Job Co')
                ->where('employers.data.0.jobs_count', 0)
                ->where('selected.about_company', 'Still searching for first hire.')
                ->has('activities.0.title')
                ->where('can_manage', true));

        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $employer->id)
                ->where('type', UserActivityType::UpdatedProfile->value)
                ->exists()
        );
    }

    public function test_recruiter_can_tag_and_edit_company_without_users_manage(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $tag = Tag::query()->create(['name' => 'Priority Account', 'slug' => 'priority-account', 'is_active' => true]);

        $this->assertFalse($recruiter->hasPermission(PermissionName::UsersManage->value));
        $this->assertTrue($recruiter->hasPermission(PermissionName::EmployersManage->value));

        $this->actingAs($viewer)
            ->put(route('admin.employers.tags.sync', $profile), ['tag_ids' => [$tag->id]])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->put(route('admin.users.tags', $employer), ['tag_ids' => [$tag->id]])
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->put(route('admin.employers.tags.sync', $profile), ['tag_ids' => [$tag->id]])
            ->assertRedirect();
        $this->assertTrue($employer->fresh()->tags->contains('id', $tag->id));

        $this->actingAs($recruiter)
            ->put(route('admin.employers.update', $profile), [
                'company_name' => 'Recruiter Updated Co',
                'benefits' => ['Visa support'],
            ])
            ->assertRedirect();
        $this->assertSame('Recruiter Updated Co', $profile->fresh()->company_name);

        $this->actingAs($recruiter)
            ->get(route('admin.employers.index', ['tag_id' => $tag->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('employers.data', 1)
                ->where('employers.data.0.id', $profile->id));
    }

    public function test_employer_cannot_access_the_admin_employer_workspace(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)->get(route('admin.employers.index'))->assertForbidden();
        $this->actingAs($employer)
            ->put(route('admin.employers.tags.sync', $profile), ['tag_ids' => []])
            ->assertForbidden();
        $this->actingAs($employer)
            ->patch(route('admin.employers.posting-restriction', $profile), ['restricted' => true])
            ->assertForbidden();
    }

    public function test_stage_four_does_not_add_permissions_or_an_employers_table(): void
    {
        $this->assertSame(34, count(AccessCatalog::permissionNames()));
        $this->assertContains(PermissionName::EmployersView->value, AccessCatalog::permissionNames());
        $this->assertContains(PermissionName::EmployersManage->value, AccessCatalog::permissionNames());
        $this->assertFalse(Schema::hasTable('employers'));
        $this->assertTrue(Schema::hasTable('employer_profiles'));
        $this->assertTrue(Schema::hasTable('employer_location'));
        $this->assertTrue(Schema::hasColumn('employer_profiles', 'benefits'));
        $this->assertTrue(Schema::hasColumn('employer_profiles', 'socials'));
        $this->assertTrue(Schema::hasColumn('employer_profiles', 'public_share'));
        $this->assertTrue(Schema::hasColumn('employer_profiles', 'posting_restricted_at'));
        $this->assertFalse(Schema::hasColumn('employer_profiles', 'logo_path'));
        $this->assertTrue(EmployerPublicShare::defaults()['enabled']);
        $this->assertTrue(EmployerPublicShare::defaults()['website']);
        $this->assertFalse(EmployerPublicShare::defaults()['locations']);
    }

    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }

    private function location(): Location
    {
        return Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Stage Four City', 'is_active' => true, 'sort_order' => 99]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function jobPayload(array $overrides = []): array
    {
        $title = JobTitle::query()->with('category.industry')->where('is_active', true)->firstOrFail();
        $location = $this->location();
        $employment = EmploymentType::query()->where('is_active', true)->first()
            ?? EmploymentType::query()->create(['name' => 'Full-time', 'is_active' => true, 'sort_order' => 1]);
        $experience = ExperienceRange::query()->where('is_active', true)->first()
            ?? ExperienceRange::query()->create(['name' => '1-3 years', 'is_active' => true, 'sort_order' => 1]);

        return [
            'title' => 'Employer Posted Role',
            'project' => 'Submitted Company',
            'industry_id' => $title->category->industry_id,
            'job_category_id' => $title->job_category_id,
            'job_title_id' => $title->id,
            'location_id' => $location->id,
            'employment_type_id' => $employment->id,
            'experience_range_id' => $experience->id,
            'salary' => 'Competitive',
            'vacancy' => 2,
            'posted_date' => now()->toDateString(),
            'closing_date' => now()->addDays(14)->toDateString(),
            'featured' => false,
            'active' => true,
            'publishing_status' => 'published',
            'description' => 'Employer job description.',
            'responsibilities' => 'Deliver work.',
            'requirements' => 'Relevant experience.',
            'what_we_offer' => 'Training.',
            ...$overrides,
        ];
    }
}
