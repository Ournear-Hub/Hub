<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScreeningComparePanelTest extends TestCase
{
    public function test_screening_list_includes_resolved_candidate_names_for_compare(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();

        $named = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Compare Panel Named Applicant',
            'user_id' => null,
        ]);

        $user = $this->makeCandidate();
        $fromProfile = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'full_name' => '',
        ]);
        $user->update(['name' => 'Compare Panel Profile Applicant']);

        $this->actingAs($admin)
            ->get(route('admin.screening.index', ['job_id' => $job->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Screening/Index')
                ->has('applications.data', 2)
                ->where('applications.data', function ($rows) use ($named, $fromProfile) {
                    $collection = collect($rows);
                    $byId = $collection->keyBy('id');

                    return $byId[$named->id]['full_name'] === 'Compare Panel Named Applicant'
                        && $byId[$fromProfile->id]['full_name'] === 'Compare Panel Profile Applicant'
                        && $byId[$fromProfile->id]['candidate']['name'] === 'Compare Panel Profile Applicant';
                }));
    }
}
