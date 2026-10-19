<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use Tests\TestCase;

class GuestJobApplyIntendedUrlTest extends TestCase
{
    public function test_guest_apply_redirects_to_login_and_returns_to_apply_after_login(): void
    {
        $candidate = $this->makeCandidate(['password' => 'Password1!']);
        $job = Job::factory()->create(['slug' => 'guest-intended-apply-'.uniqid()]);

        $this->get(route('jobs.apply', $job))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('jobs.apply', $job, absolute: false));
    }

    public function test_login_honors_same_origin_query_intended_job_apply_path(): void
    {
        $candidate = $this->makeCandidate(['password' => 'Password1!']);
        $job = Job::factory()->create();
        $applyPath = route('jobs.apply', $job, false);

        $this->get(route('login', ['intended' => $applyPath]))
            ->assertOk();

        $this->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'Password1!',
        ])->assertRedirect($applyPath);
    }

    public function test_login_ignores_intended_path_with_no_route(): void
    {
        $candidate = $this->makeCandidate(['password' => 'Password1!']);

        $this->withSession([
            'auth.intended.account' => '/account/development-programme?programme=business-operations',
        ])->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));
    }

    public function test_login_rejects_unsafe_external_intended_url(): void
    {
        $candidate = $this->makeCandidate(['password' => 'Password1!']);

        $this->withSession(['auth.intended.account' => 'https://evil.example/phish'])
            ->post(route('login.store'), [
                'login' => $candidate->email,
                'password' => 'Password1!',
            ])
            ->assertRedirect(route('account.home'));

        $this->withSession(['auth.intended.account' => '//evil.example/phish'])
            ->post(route('login.store'), [
                'login' => $candidate->email,
                'password' => 'Password1!',
            ])
            ->assertRedirect(route('account.home'));
    }

    public function test_login_page_query_intended_open_redirect_is_not_honored(): void
    {
        $candidate = $this->makeCandidate(['password' => 'Password1!']);

        $this->get(route('login', ['intended' => 'https://evil.example/phish']))
            ->assertOk();

        $this->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('account.home'));
    }

    public function test_guest_can_still_browse_public_job_detail(): void
    {
        $job = Job::factory()->create(['title' => 'Public Browse Role']);

        $this->get(route('jobs.show', $job))
            ->assertOk();
    }

    public function test_guest_intended_apply_flow_can_submit_once(): void
    {
        $candidate = $this->makeCandidate(['password' => 'Password1!']);
        $job = Job::factory()->create();

        $this->get(route('jobs.apply', $job))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'login' => $candidate->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('jobs.apply', $job, absolute: false));

        $this->giveCandidateResume($candidate);

        $this->actingAs($candidate)
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job));

        $this->assertSame(1, Application::query()->where('job_id', $job->id)->where('user_id', $candidate->id)->count());

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('jobs.apply.store', $job))
            ->assertRedirect(route('jobs.show', $job))
            ->assertSessionHasErrors('job');
    }
}
