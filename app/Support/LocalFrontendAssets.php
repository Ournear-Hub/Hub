<?php

namespace App\Support;

/**
 * Detects when public/build is out of sync with resources/js (local dev without Vite).
 */
final class LocalFrontendAssets
{
    public static function publicHomeBuildIsStale(): bool
    {
        return PublicBuildIntegrity::publicHomeBuildIsBroken(base_path());
    }

    /**
     * @return list<string>
     */
    public static function missingPublicHomeChunks(): array
    {
        return PublicBuildIntegrity::missingHomeChunksReferencedByBuiltApp(base_path());
    }

    public static function viteDevIsReachable(): bool
    {
        $hotPath = public_path('hot');
        if (! is_file($hotPath)) {
            return false;
        }

        $baseUrl = trim((string) file_get_contents($hotPath));
        if ($baseUrl === '' || ! str_starts_with($baseUrl, 'http')) {
            return false;
        }

        foreach (self::viteProbeBaseUrls($baseUrl) as $probeUrl) {
            if (self::probeViteClientUrl($probeUrl.'/@vite/client')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function viteProbeBaseUrls(string $baseUrl): array
    {
        $trimmed = rtrim($baseUrl, '/');
        $urls = [$trimmed];

        if (preg_match('#^http://127\.0\.0\.1:(\d+)$#', $trimmed, $matches) === 1) {
            $urls[] = 'http://localhost:'.$matches[1];
        } elseif (preg_match('#^http://localhost:(\d+)$#', $trimmed, $matches) === 1) {
            $urls[] = 'http://127.0.0.1:'.$matches[1];
        }

        return array_values(array_unique($urls));
    }

    private static function probeViteClientUrl(string $probeUrl): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($probeUrl, false, $context);
        if ($body === false) {
            return false;
        }

        if (isset($http_response_header[0]) && preg_match('/\s200\s/', $http_response_header[0])) {
            return true;
        }

        return is_string($body) && str_contains($body, 'vite');
    }

    public static function removeStaleHotFile(): void
    {
        $hotPath = public_path('hot');
        if (is_file($hotPath) && ! self::viteDevIsReachable()) {
            @unlink($hotPath);
        }
    }
}
