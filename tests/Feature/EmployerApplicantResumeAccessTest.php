<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationResumeDeliveryService;
use App\Support\EmployerVisibleCandidate;
use App\Support\QaHiringFixtures;
use App\Support\ReadablePdfFixture;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Employer application resume snapshot access (added — not run; deferred to consolidated testing phase).
 */
class EmployerApplicantResumeAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('qa:hiring-fixtures');
    }

    private function employerA(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();
    }

    private function employerB(): User
    {
        return User::query()->where('email', QaHiringFixtures::EMPLOYER_B_EMAIL)->firstOrFail();
    }

    private function candidate(): User
    {
        return User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
    }

    private function applicationWithSnapshot(string $path = 'qa-snapshot.pdf'): Application
    {
        $job = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
        $application = Application::query()
            ->where('job_id', $job->id)
            ->where('user_id', $this->candidate()->id)
            ->firstOrFail();

        Storage::disk('resumes')->put($path, ReadablePdfFixture::minimalOnePage());
        $application->forceFill(['resume_path' => $path])->save();

        return $application->fresh() ?? $application;
    }

    public function test_authorized_employer_can_download_application_resume(): void
    {
        $application = $this->applicationWithSnapshot('employer-resume-download.pdf');

        $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application))
            ->assertOk();
    }

    public function test_authorized_employer_can_preview_pdf_inline(): void
    {
        $application = $this->applicationWithSnapshot('employer-resume-inline.pdf');

        $response = $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', [
                'application' => $application,
                'disposition' => 'inline',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_cross_employer_resume_access_is_forbidden(): void
    {
        $application = $this->applicationWithSnapshot();

        $this->actingAs($this->employerB())
            ->get(route('account.employer.applications.resume', $application))
            ->assertForbidden();
    }

    public function test_candidate_cannot_use_employer_resume_route(): void
    {
        $application = $this->applicationWithSnapshot();

        $this->actingAs($this->candidate())
            ->get(route('account.employer.applications.resume', $application))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/employer-applications/'.$application->id.'/resume']));
    }

    public function test_missing_file_returns_not_found_even_when_db_path_is_set(): void
    {
        $job = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
        $application = Application::query()
            ->where('job_id', $job->id)
            ->where('user_id', $this->candidate()->id)
            ->firstOrFail();

        $application->forceFill(['resume_path' => 'missing-on-disk.pdf'])->save();
        Storage::disk('resumes')->delete('missing-on-disk.pdf');

        $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application))
            ->assertNotFound();
    }

    public function test_path_traversal_in_resume_path_is_rejected(): void
    {
        $application = $this->applicationWithSnapshot();
        $application->forceFill(['resume_path' => '../secret.pdf'])->save();

        $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application))
            ->assertNotFound();
    }

    public function test_legacy_candidate_files_disk_fallback_for_snapshot_path(): void
    {
        $application = $this->applicationWithSnapshot();
        $legacyPath = 'legacy-library-snapshot.pdf';
        Storage::disk('candidate_files')->put($legacyPath, ReadablePdfFixture::minimalOnePage());
        Storage::disk('resumes')->delete($application->resumeStoragePath() ?? '');
        $application->forceFill(['resume_path' => $legacyPath])->save();

        $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application))
            ->assertOk();
    }

    public function test_employer_visible_candidate_has_resume_requires_storage_file(): void
    {
        $application = $this->applicationWithSnapshot('visible-candidate-resume.pdf');
        $payload = EmployerVisibleCandidate::fromApplication($application);
        $this->assertTrue($payload['has_resume']);
        $this->assertNotEmpty($payload['resume_url']);

        Storage::disk('resumes')->delete('visible-candidate-resume.pdf');
        $payloadMissing = EmployerVisibleCandidate::fromApplication($application->fresh() ?? $application);
        $this->assertFalse($payloadMissing['has_resume']);
        $this->assertNull($payloadMissing['resume_url']);
    }

    public function test_delivery_service_prefers_snapshot_on_resumes_disk_over_library(): void
    {
        $application = $this->applicationWithSnapshot('snapshot-a.pdf');
        $snapshotBytes = ReadablePdfFixture::minimalOnePage();
        Storage::disk('resumes')->put('snapshot-a.pdf', $snapshotBytes);
        Storage::disk('candidate_files')->put('snapshot-a.pdf', 'different-content');

        $resolved = app(ApplicationResumeDeliveryService::class)->resolveSnapshot($application);
        $this->assertNotNull($resolved);
        $this->assertSame('resumes', $resolved->disk);
    }

    public function test_download_filename_uses_business_id_and_extension(): void
    {
        $application = $this->applicationWithSnapshot('named-snapshot.pdf');

        $response = $this->actingAs($this->employerA())
            ->get(route('account.employer.applications.resume', $application));

        $response->assertOk();
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString($application->business_id, $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
    }
}
