<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Location;
use App\Models\Qualification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidatesWorkspaceTest extends TestCase
{
    public function test_admin_can_list_search_and_open_candidate_workspace(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate', 'email' => 'asha@example.com']);
        $other = $this->makeCandidate(['name' => 'Other Person']);
        $profile = $candidate->candidateProfile;
        $profile?->update(['mobile' => '07123456789', 'location' => 'Manchester']);

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', ['search' => 'Asha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Candidates/Index')
                ->has('candidates.data', 1)
                ->where('candidates.data.0.name', 'Asha Candidate')
                ->where('candidates.data.0.location', 'Manchester')
                ->where('candidates.data.0.business_id', $profile?->business_id));

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', [
                'search' => $profile?->business_id,
                'candidate' => $profile?->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.id', $profile?->id)
                ->where('selected.email', 'asha@example.com')
                ->where('selected.business_id', $profile?->business_id)
                ->where('filters.candidate', (string) $profile?->id));

        $this->assertNotSame($other->candidateProfile?->id, $profile?->id);
    }

    public function test_existing_candidate_null_fks_still_display_and_master_data_can_be_saved(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['name' => 'Legacy Candidate']);
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'location' => 'Bristol',
            'experience' => '3-5 years',
            'qualification' => 'NVQ',
        ]);
        $this->assertNull($profile->fresh()->location_id);

        $location = Location::query()->firstOrCreate(
            ['name' => 'Bristol'],
            ['is_active' => true, 'sort_order' => 1],
        );
        $qualification = Qualification::findByName('Degree');

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', ['candidate' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.location', 'Bristol')
                ->where('selected.experience', '3-5 years')
                ->where('selected.qualification', 'NVQ')
                ->where('selected.location_id', null));

        $this->actingAs($admin)
            ->from(route('admin.candidates.index', ['candidate' => $profile->id]))
            ->put(route('admin.candidates.update', $profile), [
                'name' => 'Legacy Candidate',
                'mobile' => '07999999999',
                'location_id' => $location->id,
                'qualification_id' => $qualification?->id,
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame($location->id, $profile->location_id);
        $this->assertSame('Bristol', $profile->location);
        $this->assertSame('07999999999', $profile->mobile);
        $this->assertSame('3-5 years', $profile->experience);
        $this->assertSame($qualification?->id, $profile->qualification_id);
        $this->assertSame('Degree', $profile->qualification);
        $this->assertMatchesRegularExpression('/^ONT-C-\d{6}$/', (string) $profile->business_id);
    }

    public function test_admin_can_download_candidate_application_resume(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $path = 'candidate-resume.pdf';
        Storage::disk('resumes')->put($path, 'resume-bytes');
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'resume_path' => $path,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.candidates.index', ['candidate' => $candidate->candidateProfile?->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications', 1)
                ->where('applications.0.has_resume', true));

        $this->actingAs($admin)
            ->get(route('admin.applications.resume', $application))
            ->assertOk();
    }

    public function test_candidate_and_employer_cannot_access_admin_candidates(): void
    {
        $this->actingAs($this->makeCandidate())
            ->get(route('admin.candidates.index'))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.candidates.index'))
            ->assertForbidden();
    }
}
