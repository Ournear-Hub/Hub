<?php

namespace Tests\Feature\Api\V1;

use App\Models\Job;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileCandidateCommunicationApiTest extends TestCase
{
    private function bearer($user): self
    {
        Sanctum::actingAs($user, ['account:mobile']);

        return $this;
    }

    public function test_notifications_index_and_mark_read(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => now()]);
        $job = Job::factory()->create();
        $this->applyAsCandidate($user, $job);

        $this->bearer($user)
            ->getJson(route('api.v1.account.notifications.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['unread_count']]);

        $notificationId = $user->fresh()->notifications()->value('id');
        $this->assertNotNull($notificationId);

        $this->bearer($user)
            ->postJson(route('api.v1.account.notifications.read', $notificationId))
            ->assertOk();
    }

    public function test_application_messages_require_ownership(): void
    {
        $owner = $this->makeCandidate(['email_verified_at' => now()]);
        $other = $this->makeCandidate(['email_verified_at' => now()]);
        $employer = $this->makeEmployer(['email_verified_at' => now()]);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = $this->applyAsCandidate($owner, $job);

        $this->bearer($other)
            ->getJson(route('api.v1.account.applications.messages.show', $application))
            ->assertForbidden();

        $this->bearer($owner)
            ->postJson(route('api.v1.account.applications.messages.store', $application), [
                'body' => 'Hello from mobile.',
            ])
            ->assertCreated();

        $this->bearer($owner)
            ->getJson(route('api.v1.account.applications.messages.show', $application))
            ->assertOk()
            ->assertJsonPath('data.messages.0.body', 'Hello from mobile.');
    }

    public function test_public_job_filters_endpoint(): void
    {
        Job::factory()->create(['industry' => 'Mining']);

        $this->getJson(route('api.v1.jobs.filters'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['industries', 'locations', 'categories']]);
    }
}