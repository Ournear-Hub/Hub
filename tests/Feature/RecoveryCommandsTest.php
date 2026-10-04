<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecoveryCommandsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function audit_recovered_fks_command_runs_read_only(): void
    {
        $this->artisan('ournear:audit-recovered-fks')
            ->assertExitCode(0);
    }

    #[Test]
    public function repair_recovered_data_command_defaults_to_dry_run(): void
    {
        $this->artisan('ournear:repair-recovered-data')
            ->assertExitCode(0);
    }

    #[Test]
    public function reconcile_recovered_applications_command_defaults_to_dry_run(): void
    {
        $this->artisan('ournear:reconcile-recovered-applications')
            ->assertExitCode(0);
    }
}
