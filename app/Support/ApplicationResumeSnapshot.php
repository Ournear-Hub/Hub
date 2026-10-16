<?php

namespace App\Support;

/**
 * Resolved application resume snapshot on storage (immutable apply-time copy).
 */
final class ApplicationResumeSnapshot
{
    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly string $extension,
    ) {}

    public function isPdf(): bool
    {
        return $this->extension === 'pdf';
    }
}
