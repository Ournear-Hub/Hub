<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('resumes');
        Storage::fake('public');
    }

    protected function makeAdmin(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    protected function makeCandidate(array $overrides = []): User
    {
        return User::factory()->candidate()->create($overrides);
    }

    protected function makeEmployer(array $overrides = []): User
    {
        return User::factory()->employer()->create($overrides);
    }

    protected function fakeResume(string $name = 'resume.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n%%EOF\n"
        );
    }
}
