<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\User;
use App\Notifications\HiringEmailVerificationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FrontendRebuildStage27Test extends TestCase
{
    public function test_about_page_renders_from_cms(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('About')
                ->has('cms.sections'));
    }

    public function test_candidate_home_includes_hiring_entry(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Home'));
    }

    public function test_candidate_cannot_open_employer_jobs_until_hiring_is_activated(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('account.employer.jobs.index'))
            ->assertForbidden();
    }

    public function test_candidate_activates_hiring_with_same_verified_email_without_changing_role(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Dev Hire Co',
                'hiring_email' => $candidate->email,
            ])
            ->assertRedirect();

        $candidate->refresh();

        $this->assertSame(UserRole::Candidate, $candidate->role);
        $this->assertTrue($candidate->employerProfile()->exists());
        $this->assertTrue($candidate->hiringServicesActive());

        $this->actingAs($candidate)
            ->get(route('account.employer.jobs.index'))
            ->assertOk();
    }

    public function test_different_hiring_email_requires_verification(): void
    {
        Notification::fake();

        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->from(route('account.hiring'))
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Dev Hire Co',
                'hiring_email' => 'recruiter-dev@example.com',
            ])
            ->assertRedirect(route('account.hiring'))
            ->assertSessionHasErrors('hiring_email');

        $this->assertFalse(EmployerProfile::query()->where('user_id', $candidate->id)->exists());
        Notification::assertSentTo($candidate, HiringEmailVerificationNotification::class);

        $hash = sha1('recruiter-dev@example.com');
        $url = URL::temporarySignedRoute('account.hiring.email.verify', now()->addHour(), [
            'id' => $candidate->id,
            'hash' => $hash,
        ]);

        $this->actingAs($candidate)->get($url)->assertRedirect(route('account.hiring'));
        $this->assertTrue($candidate->fresh()->hiringEmailVerified());

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Dev Hire Co',
                'hiring_email' => 'recruiter-dev@example.com',
            ])
            ->assertRedirect();

        $this->assertTrue($candidate->fresh()->hiringServicesActive());
    }

    public function test_mobile_is_required_for_hiring_but_not_for_candidate_profile(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk();

        $this->actingAs($candidate)
            ->from(route('account.hiring'))
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Dev Hire Co',
            ])
            ->assertRedirect(route('account.hiring'))
            ->assertSessionHasErrors('mobile');
    }

    public function test_jobs_page_accepts_company_salary_work_mode_and_sort(): void
    {
        $this->get('/jobs?company=Acme&salary=10&work_mode=Remote&sort=title')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('filters.company', 'Acme')
                ->where('filters.work_mode', 'Remote')
                ->where('filters.sort', 'title'));
    }
}
