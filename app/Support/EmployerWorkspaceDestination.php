<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Preserves the post-activation destination for employer workspace entry (job create, ATS, staffing, recruitment support).
 */
class EmployerWorkspaceDestination
{
    public const SESSION_KEY = 'employer.workspace.destination';

    /** @var list<string> */
    public const ALLOWED_PREFIXES = [
        '/account/jobs/create',
        '/account/jobs',
        '/account/hiring',
        '/account/recruitment-support',
        '/account/enquiries',
        '/account/company',
        '/account/talent-pool',
    ];

    public static function remember(Request $request, ?string $destination = null): void
    {
        $dest = $destination ?? $request->query('destination');
        if (! is_string($dest) || ! self::isAllowedPath($dest)) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, self::normalizePath($dest));
    }

    public static function pull(Request $request, string $fallback): string
    {
        $dest = $request->session()->pull(self::SESSION_KEY);
        if (! is_string($dest) || ! self::isAllowedPath($dest)) {
            return $fallback;
        }

        return self::normalizePath($dest);
    }

    public static function activationRoute(string $destination): string
    {
        $path = self::normalizePath($destination);

        return route('account.hiring', ['destination' => $path]);
    }

    private static function normalizePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsed = parse_url($path, PHP_URL_PATH);

            return is_string($parsed) && $parsed !== '' ? $parsed : $path;
        }

        return $path;
    }

    private static function isAllowedPath(string $path): bool
    {
        $normalized = self::normalizePath($path);
        if (! str_starts_with($normalized, '/') || str_starts_with($normalized, '//')) {
            return false;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($normalized === $prefix || str_starts_with($normalized, rtrim($prefix, '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
