<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileBrowserProfileMutationTest extends TestCase
{
    public function test_mobile_user_agent_blocks_company_profile_update(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'])
            ->actingAs($employer)
            ->post(route('account.profile.update'), [
                '_company_profile' => '1',
                'company_name' => 'Mobile Block Co',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_desktop_user_agent_allows_company_profile_update(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'])
            ->actingAs($employer)
            ->post(route('account.profile.update'), [
                '_company_profile' => '1',
                'company_name' => 'Desktop Save Co',
                'contact_person' => $employer->name,
                'mobile_number' => '+919876543299',
                'industry_id' => $this->industryId(),
            ])
            ->assertRedirect();

        $employer->refresh();
        $this->assertSame('Desktop Save Co', $employer->employerProfile?->company_name);
    }

    public function test_inertia_shares_mobile_profile_view_only_flag_for_mobile_user_agent(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now()]);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Pixel 7)'])
            ->actingAs($employer)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('ui.mobile_profile_view_only', true));
    }
}
