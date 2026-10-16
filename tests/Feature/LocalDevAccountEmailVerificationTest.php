<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLocalEnvironment;
use App\Models\SecurityActivity;
use App\Models\User;
use App\Services\LocalDevAccountEmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LocalDevAccountEmailVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }

    public function test_artisan_command_verifies_one_account_in_testing_environment(): void
    {
        $user = $this->makeCandidate([
            'email' => 'local-verify-cmd@example.test',
            'email_verified_at' => null,
        ]);
        $this->assertFalse($user->emailVerified());

        $exit = Artisan::call('ournear:local-verify-account-email', [
            'identifier' => 'local-verify-cmd@example.test',
        ]);

        $this->assertSame(0, $exit);
        $user->refresh();
        $this->assertTrue($user->emailVerified());

        $this->assertDatabaseHas('security_activities', [
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
        ]);

        $activity = SecurityActivity::query()
            ->where('subject_id', $user->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame('verify_account_email', $activity->metadata['local_dev_action'] ?? null);
    }

    public function test_service_refuses_non_local_environments(): void
    {
        $this->app->detectEnvironment(fn () => 'staging');

        $user = $this->makeCandidate(['email' => 'staging-block@example.test', 'email_verified_at' => null]);
        $service = app(LocalDevAccountEmailVerificationService::class);

        $this->expectException(\RuntimeException::class);
        $service->assertAllowedEnvironment();
    }

    public function test_local_environment_middleware_blocks_non_local_requests(): void
    {
        $this->app->detectEnvironment(fn () => 'staging');

        $middleware = new EnsureLocalEnvironment;
        $request = Request::create('/admin/users/1/local-verify-email', 'POST');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_admin_can_mark_account_email_verified_via_route_in_testing_environment(): void
    {
        $admin = $this->makeStaff(\App\Enums\StaffRoleSlug::SuperAdmin->value);
        $target = User::factory()->candidate()->create([
            'email' => 'admin-route-verify@example.test',
            'email_verified_at' => null,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.local-verify-email', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertTrue($target->emailVerified());
    }
}
