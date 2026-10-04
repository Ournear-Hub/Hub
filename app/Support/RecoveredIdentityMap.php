<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * File-backed legacy ID maps for deterministic recovery (production-only data; not committed).
 *
 * storage/app/recovery/user_id_map.json  {"123": 456, ...}
 * storage/app/recovery/job_id_map.json   {"10": 25, ...}
 */
final class RecoveredIdentityMap
{
    private const RECOVERY_DIR = 'app/recovery';

    /**
     * @return array<int, int>
     */
    public function userIdMap(): array
    {
        return $this->readIntMap('user_id_map.json');
    }

    /**
     * @return array<int, int>
     */
    public function jobIdMap(): array
    {
        return $this->readIntMap('job_id_map.json');
    }

    public function mappedUserId(int $legacyUserId): ?int
    {
        $map = $this->userIdMap();

        return $map[$legacyUserId] ?? null;
    }

    public function mappedJobId(int $legacyJobId): ?int
    {
        $map = $this->jobIdMap();

        return $map[$legacyJobId] ?? null;
    }

    public function recoveryDirectory(): string
    {
        $path = storage_path(self::RECOVERY_DIR);
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    /**
     * @return array<int, int>
     */
    private function readIntMap(string $filename): array
    {
        $path = storage_path(self::RECOVERY_DIR.'/'.$filename);
        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (! is_array($decoded)) {
            return [];
        }

        $map = [];
        foreach ($decoded as $legacy => $current) {
            if (is_numeric($legacy) && is_numeric($current)) {
                $map[(int) $legacy] = (int) $current;
            }
        }

        return $map;
    }
}
