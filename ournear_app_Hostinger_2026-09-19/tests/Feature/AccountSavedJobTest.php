<?php

namespace Tests\Feature;

use App\Enums\UserActivityType;
use App\Models\Application;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountSavedJobTest extends TestCase
{
    public function test_candidate_can_save_an_open_job(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['title' => 'Site Engineer']);

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('account.saved-jobs.store', $job), [
                'user_id' => 999999,
                'candidate_profile_id' => 999999,
            ])
            ->assertRedirect(route('jobs.show', $job));

        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
        $this->assertSame(1, SavedJob::query()->count());
        $this->assertSame(0, Application::query()->count());
        $this->assertSame(
            1,
            $candidate->activities()->where('type', UserActivityType::SavedJob)->count()
        );
    }

    public function test_duplicate_save_does_not_create_a_second_row(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $job))->assertRedirect();
        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $job))->assertRedirect();

        $this->assertSame(1, SavedJob::query()->where('user_id', $candidate->id)->where('job_id', $job->id)->count());
        $this->assertSame(
            1,
            $candidate->activities()->where('type', UserActivityType::SavedJob)->count()
        );
    }

    public function test_unique_database_constraint_rejects_duplicate_saved_jobs(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        SavedJob::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        try {
            SavedJob::query()->create([
                'user_id' => $candidate->id,
                'job_id' => $job->id,
            ]);
            $this->fail('Expected the unique saved-job constraint to reject a duplicate row.');
        } catch (UniqueConstraintViolationException|QueryException) {
            $this->assertSame(1, SavedJob::query()->count());
        }
    }

    public function test_candidate_can_unsave_and_unsave_is_idempotent(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $job))->assertRedirect();

        $this->actingAs($candidate)
            ->from(route('account.saved-jobs.index'))
            ->delete(route('account.saved-jobs.destroy', $job))
            ->assertRedirect(route('account.saved-jobs.index'));

        $this->assertDatabaseMissing('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);

        $this->actingAs($candidate)
            ->delete(route('account.saved-jobs.destroy', $job))
            ->assertRedirect();

        $this->assertSame(0, SavedJob::query()->count());
        $this->assertSame(0, Application::query()->count());
    }

    public function test_candidate_lists_saved_jobs_newest_first_with_job_details(): void
    {
        $candidate = $this->makeCandidate();
        $older = Job::factory()->create([
            'title' => 'Older Saved Role',
            'project' => 'North Works',
            'location' => 'Leeds',
            'employment_type' => 'Full-time',
        ]);
        $newer = Job::factory()->create([
            'title' => 'Newer Saved Role',
            'project' => 'South Works',
            'location' => 'Manchester',
            'employment_type' => 'Contract',
        ]);

        $olderSave = SavedJob::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $older->id,
        ]);
        $olderSave->forceFill([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ])->save();
        SavedJob::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $newer->id,
        ]);

        $this->actingAs($candidate)
            ->get(route('account.saved-jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/SavedJobs')
                ->has('savedJobs.data', 2)
                ->where('savedJobs.data.0.title', 'Newer Saved Role')
                ->where('savedJobs.data.0.project', 'South Works')
                ->where('savedJobs.data.0.location', 'Manchester')
                ->where('savedJobs.data.0.employment_type', 'Contract')
                ->where('savedJobs.data.0.status', 'open')
                ->where('savedJobs.data.0.can_view', true)
                ->missing('savedJobs.data.0.user_id')
                ->missing('savedJobs.data.0.job_id')
                ->where('savedJobs.data.1.title', 'Older Saved Role')
                ->where('filters.status', 'all'));
    }

    public function test_closed_saved_jobs_remain_and_can_be_filtered(): void
    {
        $candidate = $this->makeCandidate();
        $open = Job::factory()->create(['title' => 'Open Saved Role']);
        $closed = Job::factory()->create(['title' => 'Later Closed Role']);

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $open))->assertRedirect();
        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $closed))->assertRedirect();

        $closed->update([
            'closing_date' => now()->subDay(),
        ]);

        $this->assertTrue($closed->fresh()->isClosed());
        $this->assertSame(2, SavedJob::query()->where('user_id', $candidate->id)->count());

        $this->actingAs($candidate)
            ->get(route('account.saved-jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('savedJobs.data', 2)
                ->where('savedJobs.data', function ($jobs) use ($closed) {
                    $byTitle = collect($jobs)->keyBy('title');

                    return $byTitle['Later Closed Role']['status_label'] === 'Closed'
                        && $byTitle['Later Closed Role']['can_view'] === false
                        && $byTitle['Later Closed Role']['slug'] === $closed->slug
                        && $byTitle['Open Saved Role']['status'] === 'open';
                }));

        $this->actingAs($candidate)
            ->get(route('account.saved-jobs.index', ['status' => 'open']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('savedJobs.data', 1)
                ->where('savedJobs.data.0.title', 'Open Saved Role')
                ->where('filters.status', 'open'));

        $this->actingAs($candidate)
            ->get(route('account.saved-jobs.index', ['status' => 'closed']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('savedJobs.data', 1)
                ->where('savedJobs.data.0.title', 'Later Closed Role')
                ->where('savedJobs.data.0.status_label', 'Closed'));

        $this->get(route('jobs.show', $closed))->assertNotFound();
    }

    public function test_guest_cannot_save_a_job(): void
    {
        $job = Job::factory()->create();

        $this->post(route('account.saved-jobs.store', $job))->assertRedirect(route('login'));
        $this->get(route('account.saved-jobs.index'))->assertRedirect(route('login'));
        $this->delete(route('account.saved-jobs.destroy', $job))->assertRedirect(route('login'));

        $this->assertSame(0, SavedJob::query()->count());
    }

    public function test_employer_cannot_use_candidate_saved_job_endpoints(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create();

        $this->actingAs($employer)->get(route('account.saved-jobs.index'))->assertForbidden();
        $this->actingAs($employer)->post(route('account.saved-jobs.store', $job))->assertForbidden();
        $this->actingAs($employer)->delete(route('account.saved-jobs.destroy', $job))->assertForbidden();

        $this->assertSame(0, SavedJob::query()->count());
    }

    public function test_candidate_cannot_manipulate_another_candidates_saved_jobs(): void
    {
        $owner = $this->makeCandidate(['username' => 'saved-owner']);
        $intruder = $this->makeCandidate(['username' => 'saved-intruder']);
        $job = Job::factory()->create();

        $this->actingAs($owner)->post(route('account.saved-jobs.store', $job))->assertRedirect();

        $this->actingAs($intruder)
            ->delete(route('account.saved-jobs.destroy', $job), [
                'user_id' => $owner->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $owner->id,
            'job_id' => $job->id,
        ]);
        $this->assertSame(0, SavedJob::query()->where('user_id', $intruder->id)->count());

        $this->actingAs($intruder)
            ->post(route('account.saved-jobs.store', $job), [
                'user_id' => $owner->id,
                'candidate_profile_id' => $owner->candidateProfile?->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, SavedJob::query()->where('user_id', $owner->id)->count());
        $this->assertSame(1, SavedJob::query()->where('user_id', $intruder->id)->count());

        $this->actingAs($intruder)
            ->get(route('account.saved-jobs.index', ['user' => $owner->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('savedJobs.data', 1));
    }

    public function test_public_jobs_index_includes_saved_state_for_candidates_and_guests(): void
    {
        $candidate = $this->makeCandidate();
        $saved = Job::factory()->create(['title' => 'Saved Public Role']);
        $unsaved = Job::factory()->create(['title' => 'Unsaved Public Role']);

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $saved))->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 2)
                ->where('jobs.data', function ($jobs) use ($saved, $unsaved) {
                    $bySlug = collect($jobs)->keyBy('slug');

                    return $bySlug[$saved->slug]['is_saved'] === true
                        && $bySlug[$unsaved->slug]['is_saved'] === false
                        && ! array_key_exists('user_id', $bySlug[$saved->slug]);
                }));

        $this->actingAs($this->makeEmployer())
            ->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('jobs.data', function ($jobs) {
                    return collect($jobs)->every(fn ($job) => $job['is_saved'] === null);
                }));

        $this->post('/logout');

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('jobs.data', function ($jobs) {
                    return collect($jobs)->every(fn ($job) => $job['is_saved'] === false);
                }));
    }

    public function test_job_detail_saved_state_persists_after_save_and_unsave(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['title' => 'Detail Saved Role']);

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.title', 'Detail Saved Role')
                ->where('job.is_saved', false));

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->post(route('account.saved-jobs.store', $job))
            ->assertRedirect(route('jobs.show', $job));

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('job.is_saved', true));

        $this->actingAs($candidate)
            ->from(route('jobs.show', $job))
            ->delete(route('account.saved-jobs.destroy', $job))
            ->assertRedirect(route('jobs.show', $job));

        $this->actingAs($candidate)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('job.is_saved', false));
    }

    public function test_guest_job_detail_is_saved_is_false(): void
    {
        $job = Job::factory()->create();

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.is_saved', false)
                ->where('job.slug', $job->slug));
    }

    public function test_unpublished_or_closed_jobs_cannot_be_newly_saved(): void
    {
        $candidate = $this->makeCandidate();
        $draft = Job::factory()->draft()->create();
        $closed = Job::factory()->closed()->create();

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $draft))->assertNotFound();
        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $closed))->assertNotFound();

        $this->assertSame(0, SavedJob::query()->count());
    }

    public function test_existing_jobs_search_show_and_guest_apply_still_work(): void
    {
        $matching = Job::factory()->create(['title' => 'Quantity Surveyor Alpha']);
        Job::factory()->create(['title' => 'Chef Bravo']);

        $this->get(route('jobs.index', ['keyword' => 'Surveyor']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.title', 'Quantity Surveyor Alpha')
                ->where('jobs.data.0.is_saved', false));

        $this->get(route('jobs.show', $matching))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.slug', $matching->slug));

        $this->post(route('jobs.apply.store', $matching))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('applications', [
            'email' => 'v12d.guest@example.com',
        ]);
        $this->assertSame(0, SavedJob::query()->count());
    }

    public function test_user_is_not_candidate_and_saved_jobs_are_user_owned(): void
    {
        $candidate = $this->makeCandidate(['username' => 'saved-identity']);
        $job = Job::factory()->create();

        $this->actingAs($candidate)->post(route('account.saved-jobs.store', $job))->assertRedirect();

        $this->assertTrue($candidate->isCandidate());
        $this->assertInstanceOf(User::class, $candidate);
        $this->assertNotNull($candidate->candidateProfile);
        $this->assertTrue(Schema::hasColumn('saved_jobs', 'user_id'));
        $this->assertTrue(Schema::hasColumn('saved_jobs', 'job_id'));
        $this->assertFalse(Schema::hasColumn('saved_jobs', 'candidate_profile_id'));
        $this->assertFalse(Schema::hasColumn('saved_jobs', 'business_id'));
        $this->assertSame($candidate->id, SavedJob::query()->value('user_id'));
        $this->assertFalse(Schema::hasColumn('users', 'resume_path'));
    }

    public function test_account_home_and_nav_include_saved_jobs_for_candidates(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Home'));

        $this->actingAs($candidate)
            ->get(route('account.saved-jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/SavedJobs'));
    }
}
