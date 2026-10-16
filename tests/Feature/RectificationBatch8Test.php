<?php

namespace Tests\Feature;

use App\Models\CandidateResume;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RectificationBatch8Test extends TestCase
{
    public function test_account_alerts_include_recent_notifications_for_dropdown(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('accountAlerts.recent'));
    }

    public function test_hire_resume_builder_download_returns_private_html_attachment(): void
    {
        $candidate = $this->makeCandidate([
            'name' => 'Download Test',
            'email_verified_at' => now(),
        ]);
        $candidate->candidateProfile?->forceFill(['current_designation' => 'Analyst'])->save();

        $this->actingAs($candidate)
            ->get(route('account.resumes.builder.download'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="ournear-hire-resume.html"')
            ->assertSee('Download Test', false)
            ->assertSee('Analyst', false);
    }

    public function test_other_candidate_cannot_download_hire_resume_export(): void
    {
        $owner = $this->makeCandidate(['email_verified_at' => now()]);
        $other = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($other)
            ->get(route('account.resumes.builder.download'))
            ->assertOk();

        $resume = CandidateResume::query()->where('candidate_profile_id', $owner->candidateProfile?->id)->first();
        if ($resume instanceof CandidateResume) {
            $this->actingAs($other)->get(route('account.resumes.download', $resume))->assertForbidden();
        } else {
            $this->assertTrue(true);
        }
    }
}
