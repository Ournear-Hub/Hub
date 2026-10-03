<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\CampaignStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\EmployerDocumentType;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PermissionName;
use App\Enums\PlacementStatus;
use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Enums\RelationshipReviewVisibility;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\ApplicationScreening;
use App\Models\Campaign;
use App\Models\Employee;
use App\Models\EmployerDocument;
use App\Models\EmployerSubmission;
use App\Models\Investor;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Placement;
use App\Models\RelationshipReview;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\Services\Admin\OperationalAnalyticsService;
use App\Services\ScorecardService;
use App\Services\ScreeningMatchService;
use App\Services\EmployerPrivateFileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage19Test extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
    public function test_date_filter_is_inclusive_and_excludes_records_outside_the_period(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();

        $insideStart = Application::factory()->create(['job_id' => $job->id]);
        $insideStart->forceFill(['created_at' => '2026-09-10 00:00:00'])->save();
        $insideEnd = Application::factory()->create(['job_id' => $job->id]);
        $insideEnd->forceFill(['created_at' => '2026-09-20 23:59:59'])->save();
        $before = Application::factory()->create(['job_id' => $job->id]);
        $before->forceFill(['created_at' => '2026-09-09 23:59:59'])->save();
        $after = Application::factory()->create(['job_id' => $job->id]);
        $after->forceFill(['created_at' => '2026-09-21 00:00:00'])->save();

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['from' => '2026-09-10', 'to' => '2026-09-20']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Index')
                ->where('period.from', '2026-09-10')
                ->where('period.to', '2026-09-20')
                ->where('period.is_default', false)
                ->where('overview.applications.value', 2)
                ->where('overview.applications.kind', 'period'));
    }

    public function test_invalid_and_inverted_dates_are_rejected_and_default_period_is_current_month(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('admin.analytics.index'))
            ->get(route('admin.analytics.index', ['from' => 'not-a-date', 'to' => '2026-09-01']))
            ->assertRedirect(route('admin.analytics.index'))
            ->assertSessionHasErrors('from');

        $this->actingAs($admin)
            ->from(route('admin.analytics.index'))
            ->get(route('admin.analytics.index', ['from' => '2026-09-20', 'to' => '2026-09-10']))
            ->assertRedirect(route('admin.analytics.index'))
            ->assertSessionHasErrors('from');

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('period.from', '2026-09-01')
                ->where('period.to', '2026-09-28')
                ->where('period.is_default', true)
                ->where('period.timezone', 'UTC'));
    }

    public function test_conversion_metrics_use_explicit_formulas_and_handle_zero_denominators(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        [$profile, $job] = $this->employerJob();

        $this->submission($admin, $profile->id, $job->id, EmployerSubmissionStatus::Selected, '2026-09-12 10:00:00');
        $this->submission($admin, $profile->id, $job->id, EmployerSubmissionStatus::Rejected, '2026-09-13 10:00:00');

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['from' => '2026-09-01', 'to' => '2026-09-28']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversions.submission_to_selection.label', 'Submission → Selection')
                ->where('conversions.submission_to_selection.formula', 'Selected submissions ÷ Submitted submissions × 100')
                ->where('conversions.submission_to_selection.numerator_label', 'Selected submissions')
                ->where('conversions.submission_to_selection.denominator_label', 'Submitted submissions')
                ->where('conversions.submission_to_selection.numerator', 1)
                ->where('conversions.submission_to_selection.denominator', 2)
                ->where('conversions.submission_to_selection.rate', 50)
                ->where('conversions.placement_to_join.label', 'Placement → Join')
                ->where('conversions.placement_to_join.formula', 'Joined employees ÷ Placements × 100')
                ->where('conversions.placement_to_join.rate', null)
                ->where('conversions.placement_to_join.empty_reason', 'No placements in the selected period.')
                ->where('submissions.decision_rate.label', 'Submission decision rate'));

        $emptyFrom = '2026-08-01';
        $emptyTo = '2026-08-05';
        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['from' => $emptyFrom, 'to' => $emptyTo]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversions.submission_to_selection.rate', null)
                ->where('conversions.submission_to_selection.denominator', 0)
                ->where('conversions.submission_to_selection.empty_reason', 'No submitted submissions in the selected period.'));

        $this->placementWithJoin($admin, $profile->id, $job->id, '2026-09-14 09:00:00', '2026-09-15 09:00:00');
        $this->placementWithJoin($admin, $profile->id, $job->id, '2026-09-16 09:00:00', null);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['from' => '2026-09-01', 'to' => '2026-09-28']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversions.placement_to_join.numerator', 1)
                ->where('conversions.placement_to_join.denominator', 2)
                ->where('conversions.placement_to_join.rate', 50)
                ->where('conversions.placement_to_join.numerator_label', 'Joined employees')
                ->where('conversions.placement_to_join.denominator_label', 'Placements'));
    }

    public function test_time_to_join_requires_both_timestamps_and_does_not_use_updated_at(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        [$profile, $job] = $this->employerJob();

        $joined = $this->placementWithJoin($admin, $profile->id, $job->id, '2026-09-11 12:00:00', '2026-09-11 12:00:00');
        $joined['application']->forceFill(['created_at' => '2026-09-01 12:00:00'])->save();
        $missingJoin = $this->placementWithJoin($admin, $profile->id, $job->id, '2026-09-12 12:00:00', null);
        $missingJoin['application']->forceFill(['created_at' => '2026-09-01 12:00:00'])->save();
        $missingJoin['placement']->forceFill(['updated_at' => '2026-09-20 12:00:00'])->save();

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['from' => '2026-09-01', 'to' => '2026-09-28']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('timeMetrics.time_to_join.eligible_count', 1)
                ->where('timeMetrics.time_to_join.average_days', 10)
                ->where('timeMetrics.primary', 'Application → Join'));

        $source = file_get_contents(app_path('Services/Admin/OperationalAnalyticsService.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString("'updated_at'", $source);
        $this->assertStringNotContainsString('"updated_at"', $source);
        $this->assertStringNotContainsString('selected_at', $source);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index', ['from' => '2026-08-01', 'to' => '2026-08-05']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('timeMetrics.time_to_join.eligible_count', 0)
                ->where('timeMetrics.time_to_join.average_days', null)
                ->where('timeMetrics.time_to_join.empty_reason', 'No data for selected period'));
    }

    public function test_unauthorized_domain_sections_are_omitted_server_side(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $this->seedPrivacyRecords();

        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $this->actingAs($recruiter)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('overview')
                ->has('documents')
                ->has('employers')
                ->missing('finance')
                ->missing('campaigns')
                ->missing('investors')
                ->where('permissions.finance', false)
                ->where('permissions.campaigns', false)
                ->where('permissions.investors', false)
                ->where('permissions.employers', true));

        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $this->actingAs($finance)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('finance')
                ->has('reviews')
                ->missing('documents')
                ->missing('campaigns')
                ->missing('investors')
                ->missing('employers')
                ->where('permissions.finance', true)
                ->where('permissions.employers', false));

        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        $this->actingAs($content)
            ->get(route('admin.analytics.index'))
            ->assertForbidden();

        $limited = $this->makeStaffWithPermissions('analytics_no_domains', [
            PermissionName::JobsView->value,
            PermissionName::ApplicationsView->value,
        ]);
        $this->actingAs($limited)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs')
                ->has('screening')
                ->missing('finance')
                ->missing('documents')
                ->missing('campaigns')
                ->missing('investors')
                ->missing('candidates'));
    }

    public function test_analytics_payload_omits_private_and_pii_fields(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        $this->seedPrivacyRecords();

        $payload = app(OperationalAnalyticsService::class)->payload($admin, [
            'from' => '2026-09-01',
            'to' => '2026-09-28',
        ]);
        $json = json_encode($payload);
        $this->assertIsString($json);
        $this->assertStringNotContainsString('SECRET_REVIEW_BODY_STAGE19', $json);
        $this->assertStringNotContainsString('secret-path-xyz.pdf', $json);
        $this->assertStringNotContainsString('pii-candidate-stage19@example.com', $json);
        $this->assertStringNotContainsString('07000900123', $json);
        $this->assertStringNotContainsString('storage_path', $json);
        $this->assertStringNotContainsString('review_notes', $json);
        $this->assertStringNotContainsString('match_percent', $json);
        $this->assertStringNotContainsString('qualitative', $json);
        $this->assertStringNotContainsString('numeric_score', $json);
        $this->assertStringNotContainsString('internal_notes', $json);
        $this->assertArrayNotHasKey('body', $payload['reviews'] ?? []);
        $this->assertSame('Indicated amount', $payload['investors']['indicated_amount']['label'] ?? null);
        $this->assertStringContainsString('non-binding', (string) ($payload['investors']['indicated_amount']['definition'] ?? ''));

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('reviews.body')
                ->has('documents')
                ->has('reviews'));

        $employer = $this->makeEmployer();
        $this->actingAs($employer)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('admin.analytics.index'))->assertForbidden();
    }

    public function test_analytics_uses_database_aggregates_without_hydrating_unbounded_collections(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        $this->seedPrivacyRecords();

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(OperationalAnalyticsService::class)->payload($admin, [
            'from' => '2026-09-01',
            'to' => '2026-09-28',
        ]);
        $log = DB::getQueryLog();
        $this->assertNotEmpty($log);

        $joined = strtolower(implode("\n", array_map(fn (array $entry) => $entry['query'], $log)));
        $this->assertStringContainsString('count(', $joined);
        $this->assertDoesNotMatchRegularExpression('/select \* from ["`]?applications["`]?/i', $joined);
        $this->assertDoesNotMatchRegularExpression('/select \* from ["`]?employees["`]?/i', $joined);
        $this->assertDoesNotMatchRegularExpression('/select \* from ["`]?relationship_reviews["`]?/i', $joined);
        $this->assertDoesNotMatchRegularExpression('/select \* from ["`]?employer_documents["`]?/i', $joined);
        $this->assertTrue(
            str_contains($joined, 'sum(') || str_contains($joined, 'avg('),
            'Expected SQL aggregates (SUM/AVG) in analytics queries.',
        );
    }

    public function test_existing_dashboards_scorecards_documents_reviews_and_permissions_remain_unchanged(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        Job::factory()->create(['active' => true, 'employer_id' => $employer->employerProfile?->id]);
        Application::factory()->create(['status' => ApplicationStatus::New]);

        $stats = app(DashboardService::class)->stats($admin);
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.recruitment.applications.value', $stats['recruitment']['applications']['value'])
                ->where('stats.platform.total_users.value', $stats['platform']['total_users']['value']));

        $dashboard = file_get_contents(resource_path('js/Pages/Admin/Dashboard.tsx'));
        $this->assertIsString($dashboard);
        $this->assertStringContainsString('Live counts from existing records', $dashboard);
        $this->assertStringContainsString('View analytics', $dashboard);
        $this->assertStringContainsString('/admin/analytics', $dashboard);

        $this->actingAs($admin)
            ->get(route('admin.work.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Work/Index'));

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->missing('overview')
                ->missing('timeMetrics')
                ->missing('finance'));

        $this->actingAs($employer)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->missing('conversions')
                ->missing('investors')
                ->missing('campaigns'));

        $match = file_get_contents(app_path('Services/ScreeningMatchService.php'));
        $this->assertIsString($match);
        $this->assertStringContainsString(ScreeningMatchService::DISCLAIMER, $match);
        $scorecard = file_get_contents(app_path('Services/ScorecardService.php'));
        $this->assertIsString($scorecard);
        $this->assertStringContainsString('never change application, screening, submission, or placement status', $scorecard);
        $this->assertSame(EmployerPrivateFileService::DISK, 'employer_files');
        $this->assertSame([RelationshipReviewVisibility::Private], RelationshipReviewVisibility::cases());
        $this->assertCount(34, PermissionName::cases());
        $names = array_map(fn (PermissionName $permission) => $permission->value, PermissionName::cases());
        $this->assertNotContains('analytics.view', $names);
        $this->assertNotContains('reports.view', $names);
        $this->assertSame('ONT-REV', BusinessIdPrefix::RelationshipReview->value);
        $this->assertSame('ONT-EDC', BusinessIdPrefix::EmployerDocument->value);
        $prefixes = array_map(fn (BusinessIdPrefix $prefix) => $prefix->name, BusinessIdPrefix::cases());
        $this->assertNotContains('Analytics', $prefixes);
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job}
     */
    private function employerJob(): array
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);

        return [$profile, $job];
    }

    private function submission(
        User $admin,
        int $employerProfileId,
        int $jobId,
        EmployerSubmissionStatus $status,
        string $submittedAt,
    ): EmployerSubmission {
        $application = Application::factory()->create(['job_id' => $jobId]);

        return EmployerSubmission::query()->create([
            'employer_profile_id' => $employerProfileId,
            'job_id' => $jobId,
            'application_id' => $application->id,
            'status' => $status,
            'submitted_by' => $admin->id,
            'submitted_at' => $submittedAt,
            'decided_at' => $status->isTerminal() ? $submittedAt : null,
            'decided_by' => $status->isTerminal() ? $admin->id : null,
        ]);
    }

    /**
     * @return array{application: Application, placement: Placement, employee: Employee}
     */
    private function placementWithJoin(
        User $admin,
        int $employerProfileId,
        int $jobId,
        string $placedAt,
        ?string $joinedAt,
    ): array {
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create([
            'job_id' => $jobId,
            'user_id' => $candidate->id,
            'status' => ApplicationStatus::Selected,
        ]);
        $submission = EmployerSubmission::query()->create([
            'employer_profile_id' => $employerProfileId,
            'job_id' => $jobId,
            'application_id' => $application->id,
            'status' => EmployerSubmissionStatus::Selected,
            'submitted_by' => $admin->id,
            'submitted_at' => $placedAt,
            'decided_at' => $placedAt,
            'decided_by' => $admin->id,
        ]);
        $placement = Placement::query()->create([
            'employer_submission_id' => $submission->id,
            'employer_profile_id' => $employerProfileId,
            'job_id' => $jobId,
            'application_id' => $application->id,
            'status' => $joinedAt ? PlacementStatus::Joined : PlacementStatus::Open,
            'created_by' => $admin->id,
        ]);
        $placement->forceFill(['created_at' => $placedAt])->save();
        $employee = null;
        if ($joinedAt !== null) {
            $employee = Employee::query()->create([
                'placement_id' => $placement->id,
                'employer_profile_id' => $employerProfileId,
                'job_id' => $jobId,
                'application_id' => $application->id,
                'employer_submission_id' => $submission->id,
                'candidate_profile_id' => $candidate->fresh()->candidateProfile?->id,
                'joined_at' => $joinedAt,
                'created_by' => $admin->id,
            ]);
        }

        return [
            'application' => $application,
            'placement' => $placement,
            'employee' => $employee,
        ];
    }

    private function seedPrivacyRecords(): void
    {
        $admin = User::query()->where('role', 'admin')->first() ?? $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id]);
        $candidate = $this->makeCandidate([
            'email' => 'pii-candidate-stage19@example.com',
            'name' => 'PII Candidate',
        ]);
        $candidate->candidateProfile?->forceFill(['mobile' => '07000900123'])->save();
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'email' => 'pii-candidate-stage19@example.com',
            'mobile' => '07000900123',
            'full_name' => 'PII Candidate',
        ]);
        ApplicationScreening::query()->create([
            'application_id' => $application->id,
            'decision' => ScreeningDecision::Shortlisted,
            'stage' => ScreeningStage::Shortlisted,
            'screened_by' => $admin->id,
            'screened_at' => '2026-09-12 10:00:00',
            'shortlisted_at' => '2026-09-12 11:00:00',
            'notes' => 'INTERNAL_SCREENING_NOTE',
        ]);
        ApplicationInterview::query()->create([
            'application_id' => $application->id,
            'scheduled_at' => '2026-09-13 10:00:00',
            'mode' => InterviewMode::Video,
            'interviewer_name' => 'Recruiter',
            'interviewer_user_id' => $admin->id,
            'outcome' => InterviewOutcome::Pending,
            'notes' => 'SECRET_SCORECARD_NOTE',
            'created_by' => $admin->id,
        ]);
        $chain = $this->placementWithJoin($admin, $profile->id, $job->id, '2026-09-14 09:00:00', '2026-09-15 09:00:00');
        $this->assertNotNull($chain['employee']);
        RelationshipReview::query()->create([
            'employee_id' => $chain['employee']->id,
            'employer_profile_id' => $profile->id,
            'candidate_profile_id' => $candidate->fresh()->candidateProfile?->id,
            'reviewer_user_id' => $employer->id,
            'direction' => RelationshipReviewDirection::EmployerToWorker,
            'rating' => 4,
            'body' => 'SECRET_REVIEW_BODY_STAGE19',
            'visibility' => RelationshipReviewVisibility::Private,
            'status' => RelationshipReviewStatus::Submitted,
        ]);
        EmployerDocument::factory()->create([
            'employer_profile_id' => $profile->id,
            'document_type' => EmployerDocumentType::CompanyDocument,
            'title' => 'Hidden pack',
            'original_filename' => 'secret-path-xyz.pdf',
            'storage_path' => 'documents/secret-path-xyz.pdf',
            'storage_disk' => 'employer_files',
            'status' => FileLifecycleStatus::Active,
            'verification_status' => DocumentVerificationStatus::Pending,
            'review_notes' => 'DO_NOT_EXPOSE_REVIEW_NOTES',
        ]);
        Invoice::query()->create([
            'employer_profile_id' => $profile->id,
            'job_id' => $job->id,
            'application_id' => $chain['application']->id,
            'employer_submission_id' => $chain['employee']->employer_submission_id,
            'placement_id' => $chain['placement']->id,
            'employee_id' => $chain['employee']->id,
            'status' => InvoiceStatus::Issued,
            'currency' => Invoice::CURRENCY,
            'amount' => '1000.00',
            'gst_rate' => '0.00',
            'gst_amount' => '0.00',
            'total_amount' => '1000.00',
            'billed_at' => '2026-09-16',
            'due_at' => '2026-09-30',
            'issued_at' => '2026-09-16 10:00:00',
            'created_by' => $admin->id,
        ]);
        $invoice = Invoice::query()->latest('id')->first();
        $this->assertNotNull($invoice);
        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'employer_profile_id' => $profile->id,
            'currency' => Invoice::CURRENCY,
            'amount' => '250.00',
            'paid_at' => '2026-09-17',
            'method' => PaymentMethod::BankTransfer,
            'recorded_by' => $admin->id,
        ]);
        Campaign::query()->create([
            'name' => 'Hidden campaign',
            'status' => CampaignStatus::Active,
            'created_by' => $admin->id,
        ]);
        $investorUser = User::factory()->create([
            'role' => \App\Enums\UserRole::Investor,
            'email' => 'hidden-investor-stage19@example.com',
        ]);
        Investor::query()->create([
            'user_id' => $investorUser->id,
            'status' => \App\Enums\InvestorStatus::Active,
            'display_name' => 'Hidden investor',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeStaffWithPermissions(string $slug, array $permissions): User
    {
        $role = Role::query()->create([
            'name' => str_replace('_', ' ', $slug),
            'slug' => $slug,
            'is_locked' => false,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all()
        );

        $user = User::factory()->create([
            'name' => ucfirst($slug),
            'email' => $slug.'@example.com',
        ]);
        $user->roles()->sync([$role->id]);
        $user->forgetPermissionCache();

        return $user->fresh() ?? $user;
    }
}
