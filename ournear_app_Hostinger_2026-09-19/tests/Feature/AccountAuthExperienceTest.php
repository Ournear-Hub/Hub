<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountAuthExperienceTest extends TestCase
{
    public function test_username_is_required_and_unique_at_registration(): void
    {
        $this->makeCandidate([
            'username' => 'taken-user',
            'email' => 'taken-email@example.com',
        ]);

        $this->post(route('register.store'), $this->registrationPayload([
            'username' => '',
        ]))->assertSessionHasErrors('username');

        $this->post(route('register.store'), $this->registrationPayload([
            'username' => 'taken-user',
        ]))->assertSessionHasErrors('username');

        $this->post(route('register.store'), $this->registrationPayload([
            'username' => 'not valid!',
        ]))->assertSessionHasErrors('username');

        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'taken-email@example.com',
        ]))->assertSessionHasErrors('email');
    }

    public function test_username_availability_endpoint_does_not_leak_account_data(): void
    {
        $this->makeCandidate(['username' => 'taken-user']);

        $this->getJson(route('register.check-username', ['username' => '']))
            ->assertStatus(422)
            ->assertExactJson(['available' => false]);

        $this->getJson(route('register.check-username', ['username' => 'no']))
            ->assertStatus(422)
            ->assertExactJson(['available' => false])
            ->assertJsonMissing(['id' => 1, 'email' => 'x']);

        $this->getJson(route('register.check-username', ['username' => 'taken-user']))
            ->assertOk()
            ->assertExactJson(['available' => false]);

        $this->getJson(route('register.check-username', ['username' => 'free-user']))
            ->assertOk()
            ->assertExactJson(['available' => true]);
    }

    public function test_registration_creates_username_and_shows_registered_email(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('account.home'))
            ->assertSessionHas('registered_email', 'v13a.new@example.com');

        $user = User::query()->where('email', 'v13a.new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('v13a-new', $user->username);
        $this->assertNull($user->email_verified_at);

        $this->actingAs($user)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('flash.registered_email', 'v13a.new@example.com'));
    }

    public function test_verified_user_can_login_with_email_or_username(): void
    {
        $user = $this->makeCandidate([
            'username' => 'verified-user',
            'email' => 'verified.user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'login' => 'verified.user@example.com',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'login' => 'verified-user',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_email_cannot_login_but_username_can(): void
    {
        $user = $this->makeCandidate([
            'username' => 'unverified-user',
            'email' => 'unverified.user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => null,
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => 'unverified.user@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login' => 'Your email is not verified. Please sign in using your username.',
            ]);
        $this->assertGuest();

        $this->post(route('login.store'), [
            'login' => 'unverified-user',
            'password' => 'password',
        ])->assertRedirect(route('account.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_rules_are_enforced_on_register_and_change(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'password' => 'password',
            'password_confirmation' => 'password',
        ]))->assertSessionHasErrors('password');

        $this->post(route('register.store'), $this->registrationPayload([
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]))->assertSessionHasErrors('password');

        $this->post(route('register.store'), $this->registrationPayload([
            'password' => 'Password1!',
            'password_confirmation' => 'Different1!',
        ]))->assertSessionHasErrors('password');

        $candidate = $this->makeCandidate(['password' => Hash::make('password')]);
        $this->actingAs($candidate)
            ->put(route('account.settings.password.update'), [
                'current_password' => 'password',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_candidate_and_employer_cannot_open_admin_but_can_see_admin_login(): void
    {
        $this->actingAs($this->makeCandidate())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->makeCandidate())
            ->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Auth/Login'));
    }

    public function test_valid_admin_reaches_dashboard_and_candidate_cannot_admin_login(): void
    {
        $admin = $this->makeAdmin(['email' => 'v13a.admin@example.com', 'password' => 'password']);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.candidates.index'))->assertOk();
        $this->get(route('admin.jobs.index'))->assertOk();
        $this->get(route('admin.applications.index'))->assertOk();

        $this->post(route('admin.logout'));
        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'email' => $this->makeCandidate(['email' => 'v13a.cand@example.com'])->email,
                'password' => 'password',
            ])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_jobs_index_includes_selected_detail_and_relative_dates(): void
    {
        $open = Job::factory()->create([
            'title' => 'Open Role',
            'posted_date' => now()->subDays(2),
            'closing_date' => now()->addDays(20),
        ]);
        Job::factory()->create([
            'title' => 'Closing Soon',
            'posted_date' => now(),
            'closing_date' => now()->addDay(),
        ]);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data')
                ->where('selected', null)
                ->where('jobs.data.0.posted_label', fn ($value) => is_string($value) && $value !== '')
                ->where('jobs.data.0.closing_label', fn ($value) => is_string($value) && $value !== '')
                ->where('jobs.data.0.apply_state', 'guest'));

        $this->get(route('jobs.index', ['job' => $open->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.slug', $open->slug)
                ->where('selected.posted_label', '2 days ago')
                ->where('selected.closing_label', '20 days left')
                ->where('selected.is_closed', false));

        $this->get(route('jobs.show', $open))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.posted_label', '2 days ago')
                ->where('job.closing_label', '20 days left'));
    }

    public function test_closed_job_label_does_not_use_negative_days(): void
    {
        $job = Job::factory()->create([
            'title' => 'Closed Role',
            'posted_date' => now()->subWeeks(2),
            'closing_date' => now()->subDay(),
            'active' => true,
        ]);

        $this->assertTrue($job->fresh()->isClosed());
        $this->assertSame('Closed', $job->fresh()->closingLabel());
        $this->assertSame('2 weeks ago', $job->fresh()->postedLabel());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'V13A Candidate',
            'username' => 'v13a-new',
            'email' => 'v13a.new@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Candidate->value,
        ], $overrides);
    }
}
