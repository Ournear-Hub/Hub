<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IntendedUrl
{
    public const ADMIN_KEY = 'auth.intended.admin';

    public const ACCOUNT_KEY = 'auth.intended.account';

    public static function remember(Request $request, ?string $path = null): void
    {
        $intended = $path ?? $request->query('intended') ?? $request->query('redirect');
        if (! is_string($intended) || ! self::isAllowedDestination($intended)) {
            return;
        }

        $request->session()->put(self::keyFor($intended), $intended);
    }

    public static function partition(Request $request): void
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || ! self::isAllowedDestination($intended)) {
            return;
        }

        $request->session()->put(self::keyFor($intended), $intended);
    }

    /**
     * @param  list<string>  $allowedPrefixes
     */
    public static function pullAllowed(Request $request, array $allowedPrefixes, string $fallback): string
    {
        $key = self::keyForPrefixes($allowedPrefixes);
        $intended = $request->session()->pull($key);
        if (! is_string($intended) || ! self::isSameOrigin($request, $intended)) {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH);
        if (! is_string($path)) {
            return $fallback;
        }

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/').'/')) {
                return self::routeExists($path) ? $intended : $fallback;
            }
        }

        $request->session()->put($key, $intended);

        return $fallback;
    }

    private static function routeExists(string $path): bool
    {
        try {
            app('router')->getRoutes()->match(Request::create($path, 'GET'));

            return true;
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
    }

    private static function isAllowedDestination(string $intended): bool
    {
        if (! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            $path = parse_url($intended, PHP_URL_PATH);
            if (! is_string($path)) {
                return false;
            }
        } else {
            $path = parse_url($intended, PHP_URL_PATH) ?: $intended;
        }

        return str_starts_with($path, '/admin')
            || str_starts_with($path, '/account')
            || str_starts_with($path, '/jobs');
    }

    private static function keyFor(string $intended): string
    {
        $path = parse_url($intended, PHP_URL_PATH) ?: $intended;

        return str_starts_with($path, '/admin') ? self::ADMIN_KEY : self::ACCOUNT_KEY;
    }

    /**
     * @param  list<string>  $allowedPrefixes
     */
    private static function keyForPrefixes(array $allowedPrefixes): string
    {
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($prefix, '/admin')) {
                return self::ADMIN_KEY;
            }
        }

        return self::ACCOUNT_KEY;
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
