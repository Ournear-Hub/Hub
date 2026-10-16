<?php

declare(strict_types=1);

/**
 * Report public/build integrity issues and source-vs-chunk staleness.
 * Does not delete chunks or edit manifest.json — partial removal breaks app-*.js lazy maps.
 */

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

use App\Support\PublicBuildIntegrity;

$manifestPath = $root.'/public/build/manifest.json';

$pageSources = [
    'resources/js/Pages/Home.tsx',
    'resources/js/Pages/Jobs/Index.tsx',
];

if (! is_file($manifestPath)) {
    fwrite(STDERR, "public/build/manifest.json missing; run npm run build.\n");
    exit(0);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (! is_array($manifest)) {
    fwrite(STDERR, "public/build/manifest.json is invalid; run npm run build.\n");
    exit(0);
}

$missingHomeChunks = PublicBuildIntegrity::missingHomeChunksReferencedByBuiltApp($root);
if ($missingHomeChunks !== []) {
    fwrite(
        STDERR,
        'Build integrity: built app references missing chunk(s): '
        .implode(', ', $missingHomeChunks)
        .". Run npm run build or use Vite dev (public/hot).\n"
    );
}

if (
    PublicBuildIntegrity::publicHomeSourceExists($root)
    && ! PublicBuildIntegrity::manifestListsPublicHome($root)
) {
    fwrite(
        STDERR,
        "Build integrity: manifest.json has no resources/js/Pages/Home.tsx entry; run npm run build.\n"
    );
}

foreach ($pageSources as $pageKey) {
    $source = $root.'/'.$pageKey;
    if (! is_file($source)) {
        continue;
    }

    $chunk = $manifest[$pageKey] ?? null;
    $chunkFile = is_array($chunk) ? ($chunk['file'] ?? null) : null;

    if (! is_string($chunkFile) || $chunkFile === '') {
        fwrite(STDERR, "Build integrity: manifest missing entry for {$pageKey}; run npm run build.\n");

        continue;
    }

    $builtPath = $root.'/public/build/'.$chunkFile;
    if (! is_file($builtPath)) {
        fwrite(STDERR, "Build integrity: missing file public/build/{$chunkFile} for {$pageKey}; run npm run build.\n");

        continue;
    }

    if (filemtime($source) > filemtime($builtPath)) {
        fwrite(
            STDOUT,
            "Notice: {$pageKey} source is newer than public/build/{$chunkFile}; prefer Vite dev or run npm run build.\n"
        );
    }
}

if (PublicBuildIntegrity::publicHomeBuildIsBroken($root)) {
    exit(0);
}

fwrite(STDOUT, "public/build page integrity check complete.\n");
