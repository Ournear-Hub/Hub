<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\CampaignStatus;
use App\Enums\CommissionEntryStatus;
use App\Enums\JobPublishingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\CommissionEntry;
use App\Models\EmployerAttribution;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\RecruitmentActivityService;
use App\Services\ReferralAttributionService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionReadinessStage11Test extends TestCase
{
    public function test_env_example_is_labeled_local_development_only(): void
    {
        if (file_exists(base_path('.env.example'))) {
            $example = (string) file_get_contents(base_path('.env.example'));

            $this->assertStringContainsString('LOCAL DEVELOPMENT ONLY', $example);
            $this->assertStringContainsString('NOT production-ready', $example);
            $this->assertStringContainsString('APP_DEBUG=false', $example);
            $this->assertStringContainsString('Do not copy these values into Hostinger / production as-is', $example);
        } else {
            $env = (string) file_get_contents(base_path('.env'));

            $this->assertStringContainsString('LOCAL', $env);
            $this->assertStringContainsString('APP_ENV=local', $env);
            $this->assertStringContainsString('ournear_hire_local', $env);
            $this->assertStringContainsString('MAIL_MAILER=log', $env);
        }

        $this->assertCount(34, PermissionName::cases());
    }

    public function test_content_manager_dashboard_hides_applicant_and_enquiry_pii(): void
    {
        Application::factory()->create(['full_name' => 'Priya Applicant']);
        EmployerEnquiry::factory()->create([
            'company_name' => 'Hidden Enquiry Co',
            'contact_person' => 'Ramesh Contact',
        ]);

        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        $this->actingAs($content)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Priya Applicant')
            ->assertDontSee('Ramesh Contact')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('recentApplications', 0)
                ->has('recentEmployerEnquiries', 0)
                ->where('stats.applications', null)
                ->where('stats.employer_enquiries', null)
                ->where('stats.recruitment.applications.value', null)
                ->where('stats.platform.total_users.value', null));

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Priya Applicant')
            ->assertSee('Ramesh Contact')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('recentApplications', 1)
                ->where('recentApplications.0.full_name', 'Priya Applicant')
                ->has('recentEmployerEnquiries', 1)
                ->where('recentEmployerEnquiries.0.contact_person', 'Ramesh Contact')
                ->where('stats.applications', 1));
    }

    public function test_toggle_publish_and_active_go_through_lifecycle_and_keep_public_rules(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->draft()->create([
            'active' => false,
            'closing_date' => now()->addDays(21),
        ]);

        $this->assertFalse($job->isPubliclyVisible());

        $this->actingAs($admin)
            ->from(route('admin.jobs.index'))
            ->patch(route('admin.jobs.publish', $job))
            ->assertRedirect();

        $job->refresh();
        $this->assertSame(JobPublishingStatus::Published, $job->publishing_status);
        $this->assertTrue($job->active);
        $this->assertTrue($job->isPubliclyVisible());

        $this->actingAs($admin)
            ->from(route('admin.jobs.index'))
            ->patch(route('admin.jobs.active', $job))
            ->assertRedirect();

        $job->refresh();
        $this->assertSame(JobPublishingStatus::Draft, $job->publishing_status);
        $this->assertFalse($job->active);
        $this->assertFalse($job->isPubliclyVisible());

        $draft = Job::factory()->draft()->create(['active' => false]);
        $this->actingAs($admin)
            ->from(route('admin.jobs.index'))
            ->patch(route('admin.jobs.active', $draft))
            ->assertRedirect();

        $draft->refresh();
        $this->assertTrue($draft->active);
        $this->assertSame(JobPublishingStatus::Draft, $draft->publishing_status);
        $this->assertFalse($draft->isPubliclyVisible());
    }

    public function test_application_status_rejects_new_to_placed_and_keeps_valid_hops(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::New]);

        $this->actingAs($admin)
            ->from(route('admin.applications.index'))
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Placed->value,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.applications.index'))
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Selected->value,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.applications.index'))
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Interview->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);

        $review = Application::factory()->create(['status' => ApplicationStatus::New]);
        $this->actingAs($admin)
            ->patch(route('admin.applications.status', $review), [
                'status' => ApplicationStatus::Reviewing->value,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Reviewing, $review->fresh()->status);
    }

    public function test_application_is_rolled_back_when_referral_attribution_fails(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->post(route('admin.campaigns.store'), ['name' => 'Stage 11 Referral'])
            ->assertRedirect();
        $campaign = Campaign::query()->where('name', 'Stage 11 Referral')->first();
        $this->assertNotNull($campaign);
        $this->actingAs($admin)
            ->post(route('admin.campaigns.status', $campaign), ['status' => CampaignStatus::Active->value])
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.campaigns.links.store'), [
                'campaign_id' => $campaign->id,
                'path' => '/jobs',
                'code' => 'S11FAIL1',
            ])
            ->assertRedirect();

        $candidate = $this->makeCandidate(['email' => 's11.apply@example.com']);
        if (! $this->candidateHasResume($candidate)) {
            $this->giveCandidateResume($candidate);
        }
        $job = Job::factory()->create(['title' => 'Stage 11 Apply Role']);

        $this->partialMock(ReferralAttributionService::class, function ($mock) {
            $mock->shouldReceive('attributeApplication')
                ->once()
                ->andThrow(new \RuntimeException('referral write failed'));
        });
        $beforeApplications = Application::query()->count();
        $beforeReferrals = Referral::query()->count();

        $this->actingAs($candidate)
            ->withUnencryptedCookie(ReferralLink::COOKIE_NAME, 'S11FAIL1')
            ->post(route('jobs.apply.store', $job))
            ->assertStatus(500);

        $this->assertSame($beforeApplications, Application::query()->count());
        $this->assertSame($beforeReferrals, Referral::query()->count());
        $this->assertNull(
            Application::query()->where('user_id', $candidate->id)->where('job_id', $job->id)->first()
        );
    }

    public function test_payment_survives_commission_failure_and_failed_entry_can_be_retried(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->makeAdmin();
        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $partner = $this->createApprovedPartner($admin, 'Stage 11 Partners', 's11.partners@example.com');
        [, , , , , $employee] = $this->joinedEmployee();
        $employer = $employee->employer;
        $this->assertNotNull($employer);

        $this->actingAs($admin)
            ->post(route('admin.attributions.store'), [
                'channel_partner_id' => $partner->id,
                'employer_profile_id' => $employer->id,
            ])
            ->assertRedirect();
        $attribution = EmployerAttribution::query()->where('employer_profile_id', $employer->id)->first();
        $this->actingAs($admin)->post(route('admin.attributions.confirm', $attribution))->assertRedirect();

        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), [
            'amount' => '80000',
            'gst_rate' => '0',
            'billed_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
        ]);
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($invoice);
        $this->actingAs($admin)->post(route('admin.invoices.issue', $invoice));

        $this->app->bind(RecruitmentActivityService::class, Stage11FailingCommissionActivityService::class);

        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.payments.store', $invoice), [
                'amount' => '25000',
                'paid_at' => now()->toDateString(),
                'method' => PaymentMethod::BankTransfer->value,
                'reference' => 'S11-FAIL',
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $this->assertSame(1, Payment::query()->where('invoice_id', $invoice->id)->count());
        $this->assertNotSame('Cancelled', $invoice->fresh()->status->value);

        $entry = CommissionEntry::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(CommissionEntryStatus::Failed, $entry->status);
        $this->assertTrue($entry->status->canRetry());
        $this->assertStringContainsString('commission activity failed', (string) $entry->notes);

        $this->app->bind(RecruitmentActivityService::class, RecruitmentActivityService::class);
        $this->app->forgetInstance(RecruitmentActivityService::class);
        $this->app->forgetInstance(\App\Services\CommissionService::class);

        $this->actingAs($finance)
            ->from(route('admin.commission.index', ['entry' => $entry->id]))
            ->post(route('admin.commission.entries.retry', $entry))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(CommissionEntryStatus::Pending, $entry->fresh()->status);
        $this->assertSame('25000.00', (string) $entry->fresh()->basis_amount);
        $this->assertSame(1, Payment::query()->where('invoice_id', $invoice->id)->count());
    }

    private function createApprovedPartner(User $actor, string $name, string $email, int $months = 3): ChannelPartner
    {
        $this->actingAs($actor)
            ->post(route('admin.partners.store'), [
                'display_name' => $name,
                'contact_person' => $name,
                'email' => $email,
            ])
            ->assertRedirect();

        $partner = ChannelPartner::query()->where('email', $email)->first();
        $this->assertNotNull($partner);

        $this->actingAs($actor)
            ->post(route('admin.partners.agreements.store', $partner), [
                'commission_percent' => '30',
                'duration_months' => $months,
            ])
            ->assertRedirect();

        $agreement = ChannelPartnerAgreement::query()->where('channel_partner_id', $partner->id)->first();
        $this->assertNotNull($agreement);
        $this->actingAs($actor)->post(route('admin.agreements.activate', $agreement))->assertRedirect();
        $this->actingAs($actor)->post(route('admin.partners.approve', $partner))->assertRedirect();

        return $partner->fresh(['agreements', 'user']) ?? $partner;
    }

    /**
     * @return array{0: EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission, 4: Placement, 5: \App\Models\Employee}
     */
    private function joinedEmployee(
        string $company = 'Stage 11 Employer Co',
        string $employerEmail = 's11.employer@example.com',
        string $candidateName = 'Stage 11 Candidate',
    ): array {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer(['email' => $employerEmail, 'name' => $company]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->forceFill(['company_name' => $company])->save();
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Stage 11 Engineer']);
        $candidate = $this->makeCandidate([
            'name' => $candidateName,
            'email' => strtolower(str_replace(' ', '.', $candidateName)).'@example.com',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidateName,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $submission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission->fresh()));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $placement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $placement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $placement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $placement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $placement->refresh();
        $employee = $placement->employee;
        $this->assertNotNull($employee);

        return [$profile, $job, $application, $submission->fresh(), $placement, $employee];
    }
}

class Stage11FailingCommissionActivityService extends RecruitmentActivityService
{
    public function record(
        User $actor,
        RecruitmentActivityType $type,
        $application = null,
        $enquiry = null,
        ?string $body = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        $submission = null,
        $employer = null,
        $placement = null,
        $employee = null,
        $replacement = null,
        $invoice = null,
        $payment = null,
        $interview = null,
        $task = null,
        $crmLead = null,
        $crmOpportunity = null,
        $channelPartner = null,
        $partnerAgreement = null,
        $commissionEntry = null,
        $investor = null,
        $investmentOpportunity = null,
        $investorInterest = null,
        $campaign = null,
        $referralLink = null,
        $shareEvent = null,
        $referral = null,
        ?\App\Models\TalentPoolCompanySubmission $talentPoolSubmission = null,
        ?\App\Models\RecruiterTalentPoolAssignment $talentPoolAssignment = null,
        ?\App\Models\StaffingCandidateSubmission $staffingSubmission = null,
        ?\App\Enums\RecruitmentOperationalVisibility $visibility = null,
        ?array $operationalMeta = null,
    ): \App\Models\RecruitmentActivity {
        if ($type === RecruitmentActivityType::CommissionCreated) {
            throw new \RuntimeException('commission activity failed');
        }

        return parent::record(
            $actor,
            $type,
            $application,
            $enquiry,
            $body,
            $fromStatus,
            $toStatus,
            $submission,
            $employer,
            $placement,
            $employee,
            $replacement,
            $invoice,
            $payment,
            $interview,
            $task,
            $crmLead,
            $crmOpportunity,
            $channelPartner,
            $partnerAgreement,
            $commissionEntry,
            $investor,
            $investmentOpportunity,
            $investorInterest,
            $campaign,
            $referralLink,
            $shareEvent,
            $referral,
            $talentPoolSubmission,
            $talentPoolAssignment,
            $staffingSubmission,
            $visibility,
            $operationalMeta,
        );
    }
}
