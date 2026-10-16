<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicTalentPoolBuildIntegrityTest extends TestCase
{
    public function test_talent_pool_build_chunk_matches_managed_recruitment_landing(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);

        $pageKey = 'resources/js/Pages/Platform/TalentPool.tsx';
        $this->assertArrayHasKey($pageKey, $manifest);

        $relativeFile = $manifest[$pageKey]['file'] ?? null;
        $this->assertIsString($relativeFile);

        $chunkPath = public_path('build/'.$relativeFile);
        $this->assertFileExists($chunkPath);

        $chunk = (string) file_get_contents($chunkPath);

        $this->assertStringContainsString('Your Recruitment Partner', $chunk);
        $this->assertStringNotContainsString('Search public profiles', $chunk);
    }
}
