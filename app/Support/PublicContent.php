<?php

namespace App\Support;

use App\Services\WebsiteContentService;

class PublicContent
{
    /**
     * Brand chrome that is not CMS-editable.
     *
     * @return array<string, mixed>
     */
    public static function common(): array
    {
        return app(WebsiteContentService::class)->common();
    }

    /**
     * @return array{email: string, phone: string, whatsapp: string, address: string, hours: string, help: string}
     */
    public static function fallbackContact(): array
    {
        return [
            'email' => 'Office email — to be published',
            'phone' => 'Office phone — to be published',
            'whatsapp' => 'WhatsApp — to be published',
            'address' => 'Office address — to be published',
            'hours' => 'Monday to Friday, 9:00am – 6:00pm',
            'help' => 'Use the form for recruitment, training, or general enquiries.',
        ];
    }

    /**
     * @return list<array{title: string, summary: string}>
     */
    public static function services(): array
    {
        return app(WebsiteContentService::class)->coreServiceSummaries();
    }

    /**
     * @return list<string>
     */
    public static function industries(): array
    {
        return app(WebsiteContentService::class)->industryTitles();
    }
}
