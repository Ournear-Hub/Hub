<?php

namespace Tests\Feature;

use App\Enums\FreelancerStatus;
use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Models\CandidateProfile;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Freelancer;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FreelancerFeatureTest extends TestCase
{
    public function test_freelancer_page_and_form_load_for_guests(): void
    {
        $this->get(route('freelancers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Freelancers/Index')
                ->has('submission_token')
                ->has('partners')
                ->has('options.locations')
                ->has('options.industries')
                ->has('options.experiences'));
    }

    public function test_guest_can_submit_freelancer_intake_without_creating_a_user(): void
    {
        $usersBefore = User::query()->count();
        $profilesBefore = CandidateProfile::query()->count();
        $payload = $this->validPayload();

        $this->post(route('freelancers.store'), $payload)
            ->assertRedirect(route('freelancers.received'));

        $this->get(route('freelancers.received'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Freelancers/Received')
                ->where('confirmation.name', 'Priya Freelancer')
                ->where('confirmation.email', 'priya.freelancer@example.com')
                ->where('confirmation.primary_skill', 'Electrician')
                ->where('confirmation.business_id', fn ($id) => is_string($id) && str_starts_with($id, 'ONT-F-')));

        $freelancer = Freelancer::query()->where('email', 'priya.freelancer@example.com')->first();
        $this->assertNotNull($freelancer);
        $this->assertSame(FreelancerStatus::New, $freelancer->status);
        $this->assertTrue($freelancer->consent);
        $this->assertNotNull($freelancer->business_id);
        $this->assertTrue(str_starts_with($freelancer->business_id, 'ONT-F-'));
        Storage::disk('freelancer_files')->assertExists($freelancer->getAttributes()['resume_path']);
        $this->assertSame($usersBefore, User::query()->count());
        $this->assertSame($profilesBefore, CandidateProfile::query()->count());
        $this->assertDatabaseMissing('users', ['email' => 'priya.freelancer@example.com']);
    }

    public function test_validation_rejects_missing_consent_invalid_email_and_invalid_mobile(): void
    {
        $this->from(route('freelancers'))
            ->post(route('freelancers.store'), $this->validPayload(['consent' => null]))
            ->assertRedirect(route('freelancers'))
            ->assertSessionHasErrors('consent');

        $this->from(route('freelancers'))
            ->post(route('freelancers.store'), $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        $this->from(route('freelancers'))
            ->post(route('freelancers.store'), $this->validPayload(['mobile' => 'abc']))
            ->assertSessionHasErrors('mobile');

        $this->assertSame(0, Freelancer::query()->count());
    }

    public function test_invalid_resume_is_rejected_and_valid_resume_is_stored_privately(): void
    {
        $publicFilesBefore = Storage::disk('public')->allFiles();

        $this->from(route('freelancers'))
            ->post(route('freelancers.store'), $this->validPayload([
                'resume' => UploadedFile::fake()->create('notes.txt', 20),
            ]))
            ->assertSessionHasErrors('resume');

        $this->from(route('freelancers'))
            ->post(route('freelancers.store'), $this->validPayload([
                'resume' => UploadedFile::fake()->create('resume.pdf', 6000),
            ]))
            ->assertSessionHasErrors('resume');

        $this->assertSame(0, Freelancer::query()->count());

        $this->post(route('freelancers.store'), $this->validPayload())
            ->assertRedirect(route('freelancers.received'));

        $freelancer = Freelancer::query()->first();
        $this->assertNotNull($freelancer);
        $this->assertSame('freelancer_files', $freelancer->getAttributes()['resume_disk']);
        $this->assertStringNotContainsString('public', (string) $freelancer->getAttributes()['resume_path']);
        Storage::disk('freelancer_files')->assertExists($freelancer->getAttributes()['resume_path']);
        $this->assertSame($publicFilesBefore, Storage::disk('public')->allFiles());
    }

    public function test_duplicate_post_reuses_the_same_business_id(): void
    {
        $payload = $this->validPayload();

        $this->post(route('freelancers.store'), $payload)->assertRedirect(route('freelancers.received'));
        $this->post(route('freelancers.store'), $payload)->assertRedirect(route('freelancers.received'));

        $this->assertSame(1, Freelancer::query()->count());
        $this->assertSame(1, Freelancer::query()->where('business_id', Freelancer::query()->value('business_id'))->count());
    }

    public function test_same_email_can_submit_again_later_with_a_new_token(): void
    {
        $first = $this->validPayload();
        $this->post(route('freelancers.store'), $first)->assertRedirect(route('freelancers.received'));

        $this->travel(5)->minutes();

        $second = $this->validPayload([
            'submission_token' => Str::random(40),
            'primary_skill' => 'Site supervisor',
        ]);
        $this->post(route('freelancers.store'), $second)->assertRedirect(route('freelancers.received'));

        $this->assertSame(2, Freelancer::query()->where('email', 'priya.freelancer@example.com')->count());
        $this->assertSame(2, Freelancer::query()->distinct()->count('business_id'));
    }

    public function test_honeypot_and_csrf_middleware_protect_public_submission(): void
    {
        $web = app('router')->getMiddlewareGroups()['web'] ?? [];
        $this->assertTrue(collect($web)->contains(
            fn ($middleware) => is_string($middleware) && str_contains($middleware, 'ValidateCsrfToken')
        ) || class_exists(ValidateCsrfToken::class));

        $this->from(route('freelancers'))
            ->post(route('freelancers.store'), $this->validPayload(['fax' => 'bot-field']))
            ->assertSessionHasErrors('fax');

        $this->assertSame(0, Freelancer::query()->count());
    }

    public function test_guest_candidate_employer_and_unauthorised_staff_cannot_access_admin_freelancers(): void
    {
        $freelancer = Freelancer::factory()->create();

        $this->get(route('admin.freelancers.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.freelancers.show', $freelancer))->assertRedirect(route('admin.login'));
        $this->get(route('admin.freelancers.resume', $freelancer))->assertRedirect(route('admin.login'));

        $this->actingAs($this->makeCandidate())->get(route('admin.freelancers.index'))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.freelancers.show', $freelancer))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.freelancers.resume', $freelancer))->assertForbidden();

        $this->actingAs($this->makeEmployer())->get(route('admin.freelancers.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->get(route('admin.freelancers.show', $freelancer))->assertForbidden();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Finance->value))
            ->get(route('admin.freelancers.index'))
            ->assertForbidden();
        $this->actingAs($this->makeStaff(StaffRoleSlug::ContentManager->value))
            ->get(route('admin.freelancers.show', $freelancer))
            ->assertForbidden();
    }

    public function test_admin_can_list_search_filter_and_view_freelancer_detail(): void
    {
        $admin = $this->makeAdmin();
        $match = Freelancer::factory()->create([
            'name' => 'Asha Intake',
            'email' => 'asha.intake@example.com',
            'mobile' => '07700900444',
            'primary_skill' => 'Welder',
            'status' => FreelancerStatus::New,
        ]);
        Freelancer::factory()->create([
            'name' => 'Other Person',
            'email' => 'other.intake@example.com',
            'primary_skill' => 'Driver',
            'status' => FreelancerStatus::Contacted,
        ]);

        $this->actingAs($admin)->get(route('admin.freelancers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Freelancers/Index')
                ->has('freelancers.data', 2));

        $this->actingAs($admin)->get(route('admin.freelancers.index', ['search' => 'ONT-F']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('freelancers.data', 2));

        $this->actingAs($admin)->get(route('admin.freelancers.index', ['search' => 'Asha Intake']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('freelancers.data', 1)
                ->where('freelancers.data.0.name', 'Asha Intake'));

        $this->actingAs($admin)->get(route('admin.freelancers.index', ['search' => '07700900444']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('freelancers.data', 1));

        $this->actingAs($admin)->get(route('admin.freelancers.index', ['search' => 'Welder']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('freelancers.data.0.primary_skill', 'Welder'));

        $this->actingAs($admin)->get(route('admin.freelancers.index', ['status' => FreelancerStatus::New->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('freelancers.data', 1));

        $this->actingAs($admin)->get(route('admin.freelancers.index', [
            'industry_id' => $match->industry_id,
            'location_id' => $match->current_location_id,
        ]))->assertOk();

        $this->actingAs($admin)->get(route('admin.freelancers.show', $match))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Freelancers/Show')
                ->where('freelancer.business_id', $match->business_id)
                ->where('freelancer.email', 'asha.intake@example.com')
                ->missing('freelancer.resume_path')
                ->missing('freelancer.submission_token'));
    }

    public function test_admin_can_update_status_and_download_resume(): void
    {
        $admin = $this->makeAdmin();
        $freelancer = Freelancer::factory()->create(['status' => FreelancerStatus::New]);
        Storage::disk('freelancer_files')->put($freelancer->getAttributes()['resume_path'], "%PDF-1.4\n");

        $this->actingAs($admin)
            ->from(route('admin.freelancers.show', $freelancer))
            ->patch(route('admin.freelancers.status', $freelancer), ['status' => 'not-a-status'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->from(route('admin.freelancers.show', $freelancer))
            ->patch(route('admin.freelancers.status', $freelancer), ['status' => FreelancerStatus::UnderReview->value])
            ->assertRedirect();

        $freelancer->refresh();
        $this->assertSame(FreelancerStatus::UnderReview, $freelancer->status);
        $this->assertNotEmpty($freelancer->history());

        $this->actingAs($admin)
            ->get(route('admin.freelancers.resume', $freelancer))
            ->assertOk();
    }

    public function test_viewer_can_view_but_not_update_freelancer_status(): void
    {
        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $freelancer = Freelancer::factory()->create();

        $this->actingAs($viewer)->get(route('admin.freelancers.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.freelancers.show', $freelancer))->assertOk();
        $this->actingAs($viewer)
            ->patch(route('admin.freelancers.status', $freelancer), [
                'status' => FreelancerStatus::Contacted->value,
            ])
            ->assertForbidden();

        $this->assertSame(FreelancerStatus::New, $freelancer->fresh()->status);
    }

    public function test_admin_sees_possible_existing_user_without_merging(): void
    {
        $candidate = $this->makeCandidate([
            'email' => 'overlap@example.com',
            'mobile' => '07700900999',
        ]);
        $freelancer = Freelancer::factory()->create([
            'email' => 'overlap@example.com',
            'mobile' => '07700900999',
        ]);

        $this->actingAs($this->makeAdmin())->get(route('admin.freelancers.show', $freelancer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('freelancer.possible_matches', 1)
                ->where('freelancer.possible_matches.0.name', $candidate->name)
                ->where('freelancer.possible_matches.0.role', 'candidate'));

        $this->assertSame(1, User::query()->where('email', 'overlap@example.com')->count());
        $this->assertTrue(Freelancer::query()->where('email', 'overlap@example.com')->exists());
        $this->assertSame('freelancers', $freelancer->getTable());
        $this->assertSame('users', $candidate->getTable());
    }

    public function test_rbac_permissions_keep_existing_modules_visible(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);

        $this->assertContains(PermissionName::FreelancersView->value, $recruiter->permissionNames());
        $this->assertContains(PermissionName::FreelancersManage->value, $recruiter->permissionNames());
        $this->assertContains(PermissionName::JobsView->value, $recruiter->permissionNames());
        $this->assertContains(PermissionName::CandidatesView->value, $recruiter->permissionNames());
        $this->assertContains(PermissionName::ApplicationsView->value, $recruiter->permissionNames());

        $this->assertNotContains(PermissionName::FreelancersView->value, $finance->permissionNames());
        $this->assertContains(PermissionName::FinanceView->value, $finance->permissionNames());
    }

    public function test_converted_status_does_not_create_a_candidate(): void
    {
        $admin = $this->makeAdmin();
        $freelancer = Freelancer::factory()->create();
        $profilesBefore = CandidateProfile::query()->count();

        $this->actingAs($admin)
            ->patch(route('admin.freelancers.status', $freelancer), [
                'status' => FreelancerStatus::Converted->value,
            ])
            ->assertRedirect();

        $this->assertSame(FreelancerStatus::Converted, $freelancer->fresh()->status);
        $this->assertSame($profilesBefore, CandidateProfile::query()->count());
        $this->assertDatabaseMissing('users', ['email' => $freelancer->email]);
    }

    public function test_resume_is_optional_for_partnership_registration(): void
    {
        $this->post(route('freelancers.store'), $this->validPayload(['resume' => null]))
            ->assertRedirect(route('freelancers.received'));

        $freelancer = Freelancer::query()->first();
        $this->assertNotNull($freelancer);
        $this->assertTrue(str_starts_with((string) $freelancer->business_id, 'ONT-F-'));
        $this->assertNull($freelancer->getAttributes()['resume_path']);
        $this->assertDatabaseMissing('users', ['email' => 'priya.freelancer@example.com']);
        $this->assertSame(0, CandidateProfile::query()->count());
    }

    public function test_public_directory_exposes_only_safe_card_fields(): void
    {
        $pending = Freelancer::factory()->create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi.private@example.com',
            'mobile' => '07700900888',
            'status' => FreelancerStatus::New,
            'submitted_at' => now()->subDay(),
        ]);
        $approved = Freelancer::factory()->create([
            'name' => 'Asha Partner',
            'status' => FreelancerStatus::Converted,
            'submitted_at' => now(),
        ]);
        Freelancer::factory()->create([
            'name' => 'Hidden Rejected',
            'status' => FreelancerStatus::Rejected,
        ]);

        $this->get(route('freelancers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('partners', 2)
                ->where('partners.0.name', 'Asha Partner')
                ->where('partners.0.business_id', $approved->business_id)
                ->where('partners.0.status', 'Approved')
                ->where('partners.0.joined_at', now()->format('M Y'))
                ->where('partners.1.name', 'Ravi Kumar')
                ->where('partners.1.business_id', $pending->business_id)
                ->where('partners.1.works_for', $pending->worksForLabel())
                ->where('partners.1.status', 'Pending')
                ->missing('partners.0.email')
                ->missing('partners.0.mobile')
                ->missing('partners.0.resume_path')
                ->missing('partners.0.current_company'));

        $this->actingAs($this->makeCandidate())
            ->get(route('freelancers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('partners.0.status', 'Approved')
                ->where('partners.1.status', 'Pending'));

        $source = file_get_contents(resource_path('js/Pages/Freelancers/Index.tsx'));
        $this->assertIsString($source);
        $this->assertStringContainsString('profile image', $source);
        $this->assertStringContainsString('Current status', $source);
    }

    public function test_v13a_jobs_applications_and_admin_login_still_work(): void
    {
        $job = Job::factory()->create();

        $this->get(route('jobs.index'))->assertOk();
        $this->get(route('jobs.show', $job))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->get(route('for-employers'))->assertOk();
        $this->get(route('admin.login'))->assertOk();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.candidates.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.applications.index'))->assertOk();
        $this->actingAs($this->makeCandidate())->get(route('account.home'))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $location = Location::query()->active()->ordered()->first()
            ?? Location::query()->create(['name' => 'Test City', 'is_active' => true, 'sort_order' => 99]);
        $industry = Industry::query()->active()->ordered()->first()
            ?? Industry::query()->create(['name' => 'Test Industry', 'is_active' => true, 'sort_order' => 99]);
        $experience = ExperienceRange::query()->active()->ordered()->first()
            ?? ExperienceRange::query()->create(['name' => '3-5 years', 'is_active' => true, 'sort_order' => 99]);
        $employmentType = EmploymentType::query()->active()->ordered()->first();

        return array_merge([
            'submission_token' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'name' => 'Priya Freelancer',
            'mobile' => '07700900123',
            'email' => 'priya.freelancer@example.com',
            'current_location_id' => $location->id,
            'preferred_location_ids' => [$location->id],
            'primary_skill' => 'Electrician',
            'industry_id' => $industry->id,
            'experience_range_id' => $experience->id,
            'employment_type_id' => $employmentType?->id,
            'skills' => 'Electrical, Maintenance',
            'resume' => $this->fakeResume(),
            'consent' => '1',
            'fax' => '',
        ], $overrides);
    }
}
