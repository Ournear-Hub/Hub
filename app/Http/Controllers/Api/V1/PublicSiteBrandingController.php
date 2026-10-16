<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebsiteSettingService;
use Illuminate\Http\JsonResponse;

class PublicSiteBrandingController extends Controller
{
    public function show(WebsiteSettingService $settings): JsonResponse
    {
        $branding = $settings->publicBranding();

        $appName = trim((string) ($branding['website_name'] ?? ''));
        if ($appName === '') {
            $appName = 'OurNear Hire';
        }

        $logoUrl = $branding['auth_symbol_url']
            ?? $branding['logo_url']
            ?? $branding['icon_url']
            ?? null;

        return response()
            ->json([
                'data' => [
                    'app_name' => $appName,
                    'logo_url' => is_string($logoUrl) && $logoUrl !== '' ? $logoUrl : null,
                ],
            ])
            ->header('Cache-Control', 'public, max-age=300');
    }
}
