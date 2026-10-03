<?php

namespace App\Http\Controllers;

use App\Services\PublicSeoService;
use Illuminate\Http\Response;

class PublicDiscoveryController extends Controller
{
    public function robots(PublicSeoService $seo): Response
    {
        return response($seo->robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(PublicSeoService $seo): Response
    {
        return response($seo->sitemapXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
