<?php

namespace Tests\Feature;

use App\Mail\ImportedCandidateWelcomeMail;
use App\Models\User;
use App\Support\OurNearHireIdentity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HireIdentityResumeImportTest extends TestCase
{
    public function test_one_person_number_drives_candidate_recruiter_and_user_display_ids(): void
    {
        $user = $this->makeCandidate();
        $user->refresh();
        $number = (int) $user->person_number;

        $this->assertGreaterThan(0, $number);
        $identity = app(OurNearHireIdentity::class);
        $this->assertSame(OurNearHireIdentity::format($number, 'CAN'), $identity->display($user, 'CAN'));
        $this->assertSame(OurNearHireIdentity::format($number, 'REC'), $identity->display($user, 'REC'));
        $this->assertSame(OurNearHireIdentity::format($number, 'USER'), $identity->display($user, 'USER'));

        $user->forceFill(['name' => 'Renamed Person', 'person_number' => $number + 50])->save();
        $this->assertSame($number, (int) $user->fresh()->person_number);
    }

    public function test_unverified_candidate_can_open_verification_from_security_settings(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => null]);

        $this->actingAs($candidate)
            ->get(route('account.settings.security'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('profile.email_verified', false)
                ->has('profile.mobile_verified'));
    }

    public function test_guests_cannot_import_resumes(): void
    {
        $this->get(route('admin.candidates.imports.index'))->assertRedirect(route('admin.login'));
    }

    public function test_resume_import_creates_a_candidate_queues_activation_and_matches_duplicates(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $existing = $this->makeCandidate(['email' => 'already.here@example.com']);

        $created = UploadedFile::fake()->createWithContent('new-person.pdf', "%PDF-1.4\nNew Person\nnew.person@example.com\n");
        $matched = UploadedFile::fake()->createWithContent('existing.pdf', "%PDF-1.4\nalready.here@example.com\n");
        $review = UploadedFile::fake()->createWithContent('blank.pdf', "%PDF-1.4\nNo contact\n");

        $this->actingAs($admin)
            ->post(route('admin.candidates.imports.store'), [
                'files' => [$created, $matched, $review],
            ])
            ->assertRedirect(route('admin.candidates.imports.index'));

        $imported = User::query()->where('email', 'new.person@example.com')->first();
        $this->assertNotNull($imported);
        $this->assertTrue($imported->must_change_password);
        $this->assertNull($imported->email_verified_at);
        $this->assertNotNull($imported->candidateProfile);
        $this->assertSame(1, User::query()->where('email', 'already.here@example.com')->count());
        Mail::assertQueued(ImportedCandidateWelcomeMail::class, 1);

        $this->actingAs($admin)
            ->get(route('admin.candidates.imports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Candidates/ResumeImport')
                ->where('batches.0.successful', 1)
                ->where('batches.0.matched', 1)
                ->where('batches.0.needs_review', 1));
    }
}
