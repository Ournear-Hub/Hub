<?php

namespace App\Support;

use Illuminate\Http\Request;

class IntendedUrl
{
    public static function remember(Request $request, ?string $path = null): void
    {
        $intended = $path ?? $request->query('intended') ?? $request->query('redirect');
        if (! is_string($intended) || ! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return;
        }

        if (! str_starts_with($intended, '/jobs') && ! str_starts_with($intended, '/account')) {
            return;
        }

        $request->session()->put('url.intended', $intended);
    }

    /**
     * @param  list<string>  $allowedPrefixes
     */
    public static function pullAllowed(Request $request, array $allowedPrefixes, string $fallback): string
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || ! self::isSameOrigin($request, $intended)) {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH);
        if (! is_string($path)) {
            return $fallback;
        }

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/').'/')) {
                return $intended;
            }
        }

        return $fallback;
    }

    private static function isSameOrigin(Request $request, string $url): bool
    {
        if (str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        return is_string($scheme)
            && is_string($host)
            && strtolower($scheme) === strtolower($request->getScheme())
            && strtolower($host) === strtolower($request->getHost())
            && ($port ?? self::defaultPort($scheme)) === $request->getPort();
    }

    private static function defaultPort(string $scheme): int
    {
        return strtolower($scheme) === 'https' ? 443 : 80;
    }
}
