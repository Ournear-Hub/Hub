<?php

namespace App\Services;

use App\Enums\UserActivityType;
use App\Models\User;
use App\Models\WebsitePage;
use Illuminate\Http\Request;

class LegalConsentService
{
    public function __construct(private UserActivityService $activities) {}

    /**
     * @return array<string, string|null>
     */
    public function legalVersionSnapshot(): array
    {
        $terms = WebsitePage::query()->where('slug', 'terms-and-conditions')->first();
        $privacy = WebsitePage::query()->where('slug', 'privacy-policy')->first();

        return [
            'terms_slug' => 'terms-and-conditions',
            'privacy_slug' => 'privacy-policy',
            'terms_version' => $terms?->updated_at?->toIso8601String(),
            'privacy_version' => $privacy?->updated_at?->toIso8601String(),
        ];
    }

    public function recordRegistrationConsent(User $user, Request $request, string $source = 'website_signup'): void
    {
        $this->activities->record(
            $user,
            UserActivityType::LegalConsentAccepted,
            'Accepted Terms & Conditions and Privacy Policy at registration.',
            $user,
            [
                ...$this->legalVersionSnapshot(),
                'accepted_at' => now()->toIso8601String(),
                'source' => $source,
                'ip_address' => $request->ip(),
            ],
        );
    }
}
