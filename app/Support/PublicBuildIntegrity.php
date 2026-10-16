<?php

namespace App\Support;

/**
 * Detects inconsistent public/build output (partial Vite builds, missing lazy chunks).
 */
final class PublicBuildIntegrity
{
    /**
     * @return list<string> Basenames under public/build/assets/ referenced by the built app bundle but missing on disk.
     */
    public static function missingHomeChunksReferencedByBuiltApp(string $projectRoot): array
    {
        $appPath = self::builtAppScriptPath($projectRoot);
        if ($appPath === null) {
            return [];
        }

        $contents = @file_get_contents($appPath);
        if (! is_string($contents) || $contents === '') {
            return [];
        }

        if (! preg_match_all('/Home-[A-Za-z0-9_-]+\.js/', $contents, $matches)) {
            return [];
        }

        $assetsDir = rtrim($projectRoot, '/\\').'/public/build/assets';
        $missing = [];

        foreach (array_unique($matches[0]) as $basename) {
            if (! is_file($assetsDir.'/'.$basename)) {
                $missing[] = $basename;
            }
        }

        return array_values($missing);
    }

    public static function manifestListsPublicHome(string $projectRoot): bool
    {
        $manifestPath = rtrim($projectRoot, '/\\').'/public/build/manifest.json';
        if (! is_file($manifestPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        return is_array($manifest)
            && isset($manifest['resources/js/Pages/Home.tsx'])
            && is_array($manifest['resources/js/Pages/Home.tsx']);
    }

    public static function publicHomeSourceExists(string $projectRoot): bool
    {
        return is_file(rtrim($projectRoot, '/\\').'/resources/js/Pages/Home.tsx');
    }

    public static function publicHomeBuildIsBroken(string $projectRoot): bool
    {
        if (! self::publicHomeSourceExists($projectRoot)) {
            return false;
        }

        $missingChunks = self::missingHomeChunksReferencedByBuiltApp($projectRoot);
        if ($missingChunks !== []) {
            return true;
        }

        if (! self::manifestListsPublicHome($projectRoot)) {
            $appPath = self::builtAppScriptPath($projectRoot);
            if ($appPath !== null && is_file($appPath)) {
                $contents = @file_get_contents($appPath);
                if (is_string($contents) && str_contains($contents, './Pages/Home')) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function builtAppScriptPath(string $projectRoot): ?string
    {
        $manifestPath = rtrim($projectRoot, '/\\').'/public/build/manifest.json';
        if (! is_file($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            return null;
        }

        $file = $manifest['resources/js/app.tsx']['file'] ?? null;
        if (! is_string($file) || $file === '') {
            return null;
        }

        $path = rtrim($projectRoot, '/\\').'/public/build/'.$file;

        return is_file($path) ? $path : null;
    }
}
