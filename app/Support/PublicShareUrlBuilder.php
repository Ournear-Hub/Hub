<?php

namespace App\Support;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;

final class PublicShareUrlBuilder
{
    public function publicBaseUrl(): string
    {
        $configured = rtrim(trim((string) config('sharing.public_base_url', '')), '/');
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL) !== false) {
            return $configured;
        }

        $root = app(ApplicationUrlResolver::class)->effectiveRootUrl();
        if (is_string($root) && $root !== '') {
            return rtrim($root, '/');
        }

        return rtrim((string) config('app.url', 'http://localhost'), '/');
    }

    public function absolutePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->publicBaseUrl().'/'.ltrim($path, '/');
    }

    public function forJob(Job $job, ?string $referralCode = null): string
    {
        $path = route('jobs.show', $job, false);
        if (is_string($referralCode) && $referralCode !== '') {
            $path .= '?ref='.rawurlencode($referralCode);
        }

        return $this->absolutePath($path);
    }

    public function forCompany(EmployerProfile $employer): string
    {
        return $this->absolutePath(route('companies.show', $employer, false));
    }

    public function forProfile(User $user): string
    {
        return $this->absolutePath(route('profiles.show', $user, false));
    }
}
