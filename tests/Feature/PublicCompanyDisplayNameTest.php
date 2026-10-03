<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicCompanyDisplayNameTest extends TestCase
{
    public function test_public_display_company_uses_fallbacks_without_unnamed_label(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $employer->update(['name' => 'Fallback Contact Name']);
        $profile->update(['company_name' => null]);

        $this->assertSame('Fallback Contact Name', $profile->fresh()->publicDisplayCompany());
        $this->assertSame('Unnamed employer', $profile->fresh()->displayCompany());
        $this->assertStringNotContainsString('Unnamed employer', $profile->fresh()->publicDisplayCompany());
    }
}
