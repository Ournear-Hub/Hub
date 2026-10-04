<?php

namespace Tests\Unit;

use App\Support\RecoveredIdentityMap;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecoveredIdentityMapTest extends TestCase
{
    #[Test]
    public function it_reads_legacy_user_and_job_maps_from_storage(): void
    {
        $dir = storage_path('app/recovery');
        File::ensureDirectoryExists($dir);
        File::put($dir.'/user_id_map.json', json_encode(['12' => 34], JSON_THROW_ON_ERROR));
        File::put($dir.'/job_id_map.json', json_encode(['56' => 78], JSON_THROW_ON_ERROR));

        $map = new RecoveredIdentityMap;

        $this->assertSame(34, $map->mappedUserId(12));
        $this->assertSame(78, $map->mappedJobId(56));

        File::delete($dir.'/user_id_map.json');
        File::delete($dir.'/job_id_map.json');
    }
}
