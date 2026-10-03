<?php

namespace App\Mail\Concerns;

use App\Services\WebsiteSettingService;
use Illuminate\Mail\Mailables\Envelope;

trait UsesCompanyMailIdentity
{
    protected function companyEnvelope(string $subject, string $purpose = 'system'): Envelope
    {
        return app(WebsiteSettingService::class)->mailEnvelope($subject, $purpose);
    }
}
