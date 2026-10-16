<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhpunitMemoryLimitTest extends TestCase
{
    #[Test]
    public function test_phpunit_process_memory_limit_is_at_least_512_megabytes(): void
    {
        $raw = ini_get('memory_limit');
        $this->assertNotFalse($raw);
        $bytes = $this->toBytes((string) $raw);
        $this->assertGreaterThanOrEqual(512 * 1024 * 1024, $bytes, "Expected >= 512M, got {$raw}");
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return PHP_INT_MAX;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
