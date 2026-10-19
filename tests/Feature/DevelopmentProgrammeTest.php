<?php

namespace Tests\Feature;

use App\Models\DevelopmentProgrammeApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentProgrammeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_is_available_without_login(): void
    {
        $this->get(route('development-programme'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/DevelopmentProgramme')
                ->where('application', null));
    }

    public function test_guest_apply_route_requires_login(): void
    {
        $this->get(route('account.development-programme.show'))
            ->assertRedirect(route('login'));
    }

    public function test_candidate_can_submit_once_and_update_the_same_application(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.development-programme.show', ['programme' => 'digital-marketing']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Account/DevelopmentProgramme/Apply')
                ->where('selected_programme', 'digital-marketing'));

        $this->actingAs($candidate)
            ->post(route('account.development-programme.store'), [
                'programme' => 'digital-marketing',
                'motivation' => 'I want workplace experience in marketing.',
            ])
            ->assertRedirect(route('account.development-programme.show'));

        $this->actingAs($candidate)
            ->post(route('account.development-programme.store'), [
                'programme' => 'hr-recruitment',
                'motivation' => 'Updated interest.',
            ])
            ->assertRedirect(route('account.development-programme.show'));

        $this->assertSame(1, DevelopmentProgrammeApplication::query()->count());
        $this->assertDatabaseHas('development_programme_applications', [
            'user_id' => $candidate->id,
            'programme' => 'hr-recruitment',
            'status' => 'submitted',
        ]);
    }

    public function test_submitted_application_is_not_replaced_after_review_status_changes(): void
    {
        $candidate = $this->makeCandidate();
        $application = DevelopmentProgrammeApplication::query()->create([
            'user_id' => $candidate->id,
            'programme' => 'hr-recruitment',
            'motivation' => 'Original',
            'status' => 'submitted',
        ]);
        $application->forceFill(['status' => 'closed'])->save();

        $this->actingAs($candidate)
            ->from(route('account.development-programme.show'))
            ->post(route('account.development-programme.store'), [
                'programme' => 'digital-marketing',
                'motivation' => 'Should not replace',
            ])
            ->assertRedirect(route('account.development-programme.show'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('development_programme_applications', [
            'id' => $application->id,
            'user_id' => $candidate->id,
            'programme' => 'hr-recruitment',
            'motivation' => 'Original',
            'status' => 'closed',
        ]);
        $this->assertSame(1, DevelopmentProgrammeApplication::query()->count());
    }

    public function test_employer_cannot_submit_a_programme_application(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->post(route('account.development-programme.store'), [
                'programme' => 'hr-recruitment',
            ])
            ->assertForbidden();
    }
}
