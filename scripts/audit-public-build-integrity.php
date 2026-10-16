<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifestPath = $root.'/public/build/manifest.json';

if (! is_file($manifestPath)) {
    fwrite(STDERR, "MANIFEST_MISSING\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (! is_array($manifest)) {
    fwrite(STDERR, "MANIFEST_INVALID\n");
    exit(1);
}

$missing = [];
$pageKeys = [];

foreach ($manifest as $key => $entry) {
    if (! is_array($entry) || ! is_string($key)) {
        continue;
    }
    if (! str_starts_with($key, 'resources/js/Pages/')) {
        continue;
    }
    $pageKeys[] = $key;
    $file = $entry['file'] ?? null;
    if (! is_string($file) || $file === '') {
        $missing[] = $key.' (no file in manifest)';
        continue;
    }
    if (! is_file($root.'/public/build/'.$file)) {
        $missing[] = $key.' -> '.$file;
    }
}

$app = $manifest['resources/js/app.tsx']['file'] ?? null;
if (is_string($app) && ! is_file($root.'/public/build/'.$app)) {
    $missing[] = 'resources/js/app.tsx -> '.$app;
}

require $root.'/vendor/autoload.php';
$homeBroken = App\Support\PublicBuildIntegrity::publicHomeBuildIsBroken($root);
$homeChunks = App\Support\PublicBuildIntegrity::missingHomeChunksReferencedByBuiltApp($root);

echo 'pages_in_manifest='.count($pageKeys).PHP_EOL;
echo 'manifest_lists_public_home='.(App\Support\PublicBuildIntegrity::manifestListsPublicHome($root) ? 'yes' : 'no').PHP_EOL;
echo 'public_home_build_broken='.($homeBroken ? 'yes' : 'no').PHP_EOL;
echo 'missing_home_chunks_from_app='.( $homeChunks === [] ? 'none' : implode(', ', $homeChunks)).PHP_EOL;

if ($missing !== []) {
    echo 'missing_page_chunks:'.PHP_EOL;
    foreach ($missing as $line) {
        echo '  '.$line.PHP_EOL;
    }
    exit(1);
}

echo 'BUILD_INTEGRITY_OK'.PHP_EOL;
