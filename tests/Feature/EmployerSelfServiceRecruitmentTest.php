<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\PlacementStatus;
use App\Enums\CandidateInterviewResponse;
use App\Enums\CandidateNotificationEvent;
use App\Enums\ConversationContextType;
use App\Enums\ApplicationCorrectionRequestStatus;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\RejectionReason;
use App\Models\User;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EmployerSelfServiceRecruitmentTest extends TestCase
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

    private function publishedJobA(): Job
    {
        return Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->firstOrFail();
    }

    private function fixtureApplication(): Application
    {
        return Application::query()
            ->where('job_id', $this->publishedJobA()->id)
            ->where('user_id', $this->candidate()->id)
            ->firstOrFail();
    }

    public function test_employer_can_shortlist_own_job_application(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();
        $this->assertSame(ApplicationStatus::New, $application->status);

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
    }

    public function test_employer_b_cannot_shortlist_employer_a_application(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerB())
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertForbidden();
    }

    public function test_employer_can_schedule_interview_and_candidate_can_respond(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();
        $scheduledAt = now()->addDays(3)->format('Y-m-d\TH:i');

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.interview', [$job, $application]), [
                'scheduled_at' => $scheduledAt,
                'mode' => 'video',
                'location_or_link' => 'https://meet.example.test/room',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Interview, $application->status);
        $interview = $application->interviews()->first();
        $this->assertNotNull($interview);

        $this->actingAs($this->candidate())
            ->post(route('account.applications.interviews.response', [$application, $interview]), [
                'response' => CandidateInterviewResponse::Accepted->value,
                'note' => 'Looking forward to it.',
            ])
            ->assertRedirect();

        $interview->refresh();
        $this->assertSame(CandidateInterviewResponse::Accepted, $interview->candidate_response);
        $this->assertNotNull($interview->candidate_responded_at);
    }

    public function test_employer_and_candidate_messaging_uses_application_context(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.messages', [$job, $application]), [
                'body' => 'Please confirm your availability.',
            ])
            ->assertRedirect();

        $conversation = Conversation::query()
            ->where('context_type', ConversationContextType::Application)
            ->where('context_id', $application->id)
            ->first();
        $this->assertNotNull($conversation);

        $this->actingAs($this->candidate())
            ->post(route('account.applications.messages.store', $application), [
                'body' => 'I am available next week.',
            ])
            ->assertRedirect();

        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_candidate_can_submit_correction_request_once(): void
    {
        $application = $this->fixtureApplication();

        $this->actingAs($this->candidate())
            ->post(route('account.applications.correction-requests.store', $application), [
                'body' => 'My qualification year on the snapshot should read 2020.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $application->correctionRequests()->count());

        $this->actingAs($this->candidate())
            ->post(route('account.applications.correction-requests.store', $application), [
                'body' => 'Another request.',
            ])
            ->assertSessionHasErrors('body');
    }

    public function test_duplicate_shortlist_is_rejected(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertRedirect();

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertSessionHasErrors('decision');
    }

    public function test_candidate_decline_does_not_change_application_status(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.interview', [$job, $application]), [
                'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'mode' => 'phone',
            ])
            ->assertRedirect();

        $interview = $application->fresh()->interviews()->first();
        $this->assertNotNull($interview);
        $statusAfterSchedule = $application->fresh()->status;
        $this->assertSame(ApplicationStatus::Interview, $statusAfterSchedule);

        $this->actingAs($this->candidate())
            ->post(route('account.applications.interviews.response', [$application, $interview]), [
                'response' => CandidateInterviewResponse::Declined->value,
            ])
            ->assertRedirect();

        $this->assertSame($statusAfterSchedule, $application->fresh()->status);
        $this->assertSame(CandidateInterviewResponse::Declined, $interview->fresh()->candidate_response);
    }

    public function test_employer_message_notifies_candidate_in_app(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();
        $candidate = $this->candidate();

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.messages', [$job, $application]), [
                'body' => 'Please bring your portfolio.',
            ])
            ->assertRedirect();

        $this->assertTrue(
            $candidate->fresh()->notifications()
                ->where('data->event', CandidateNotificationEvent::ApplicationMessageReceived->value)
                ->where('data->context->application_id', $application->id)
                ->exists()
        );
    }

    public function test_employer_can_acknowledge_correction_request(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();

        $this->actingAs($this->candidate())
            ->post(route('account.applications.correction-requests.store', $application), [
                'body' => 'Please confirm my qualification year.',
            ])
            ->assertRedirect();

        $request = $application->correctionRequests()->first();
        $this->assertNotNull($request);

        $this->actingAs($this->employerA())
            ->patch(route('account.employer.jobs.application.correction-requests.update', [$job, $application, $request]), [
                'status' => ApplicationCorrectionRequestStatus::Acknowledged->value,
            ])
            ->assertRedirect();

        $this->assertSame(
            ApplicationCorrectionRequestStatus::Acknowledged,
            $request->fresh()->status,
        );
    }

    public function test_employer_can_reject_with_reason(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();
        $reason = RejectionReason::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($this->employerA())
            ->post(route('account.employer.jobs.application.reject', [$job, $application]), [
                'rejection_reason_id' => $reason->id,
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Rejected, $application->fresh()->status);
    }

    public function test_employer_can_record_offer_and_joining_on_own_application(): void
    {
        $job = $this->publishedJobA();
        $application = $this->fixtureApplication();
        $employer = $this->employerA();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.shortlist', [$job, $application]))
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.select', [$job, $application]))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Selected, $application->fresh()->status);

        $this->actingAs($this->employerB())
            ->post(route('account.employer.jobs.application.placement', [$job, $application]))
            ->assertForbidden();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.placement', [$job, $application]))
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.offer', [$job, $application]), [
                'offered_at' => now()->toDateString(),
                'salary' => '450000',
            ])
            ->assertRedirect();

        $placement = $application->fresh()->placements()->first();
        $this->assertNotNull($placement);
        $this->assertSame(PlacementStatus::Offered, $placement->status);

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.accept-offer', [$job, $application]))
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.confirm-joining', [$job, $application]), [
                'expected_joining_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.employer.jobs.application.join', [$job, $application]), [
                'actual_joining_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $placement->refresh();
        $this->assertSame(PlacementStatus::Joined, $placement->status);
        $this->assertSame(ApplicationStatus::Placed, $application->fresh()->status);
        $this->assertNotNull($placement->actual_joining_date);
    }

    public function test_account_talent_pool_job_routes_are_admin_only(): void
    {
        $this->actingAs($this->employerA())
            ->get(route('account.talent-pool.jobs.index'))
            ->assertRedirect('/account/hiring');

        $this->actingAs($this->employerA())
            ->get('/account/talent-pool/jobs/create')
            ->assertRedirect('/account/hiring');

        $this->actingAs($this->employerA())
            ->post(route('account.talent-pool.jobs.store'))
            ->assertNotFound();
    }
}
