<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Job;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\User;
use App\Support\QaHiringFixtures;
use App\Support\ReadablePdfFixture;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Release proof: QA fixture candidate → apply/resume → employer → admin → recruiter assignment.
 */
class QaRecruitmentJourneyProofTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('qa:hiring-fixtures');
    }

    private function candidate(): User
    {
        return User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
    }

    private function employerA(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();
    }

    private function employerB(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_B_EMAIL)->firstOrFail();
    }

    private function publishedJobA(): Job
    {
        return Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
    }

    public function test_qa_fixture_candidate_application_is_visible_through_hiring_chain(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJobA();
        $application = Application::query()
            ->where('user_id', $candidate->id)
            ->where('job_id', $job->id)
            ->firstOrFail();

        $this->assertSame('qacandidate', $candidate->username);
        $this->assertTrue($this->candidateHasResume($candidate));

        $this->actingAs($candidate)
            ->get(route('account.applications.show', $application))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Applications/Show')
                ->where('application.id', $application->id));

        Storage::disk('resumes')->put('qa-journey-resume.pdf', ReadablePdfFixture::minimalOnePage());
        $application->forceFill(['resume_path' => 'qa-journey-resume.pdf'])->save();

        $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application))
            ->assertOk();

        $this->actingAs($this->employerB())
            ->get(route('account.employer.applications.resume', $application))
            ->assertForbidden();

        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.applications.index', [
                'search' => QaHiringFixtures::CANDIDATE_EMAIL,
                'application' => $application->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->where('applications.data.0.id', $application->id));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.applications.resume', $application))
            ->assertOk();

        $application->forceFill(['status' => ApplicationStatus::Shortlisted])->save();

        $internalRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'qa-internal-recruiter@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $internalRecruiter->id,
                'assignment_type' => 'job',
                'application_id' => $application->id,
                'job_id' => $job->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = RecruiterTalentPoolAssignment::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->status->isActive());

        $this->actingAs($internalRecruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('assignments.data.0.candidate_name', $candidate->name));

        $otherRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'qa-other-recruiter@example.test']);
        $this->actingAs($otherRecruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('assignments.data', []));
    }
}
