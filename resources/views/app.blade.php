<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ ! empty($publicSeo['title']) ? $publicSeo['title'].' - '.($publicSeo['og_site_name'] ?? config('app.name', 'OurNear')) : config('app.name', 'OurNear') }}</title>
        @include('partials.public-seo')
        @if (! empty($publicBranding['favicon_url']))
            <link head-key="favicon" rel="icon" href="{{ $publicBranding['favicon_url'] }}">
        @elseif (! empty($publicBranding['icon_url']))
            <link head-key="favicon" rel="icon" href="{{ $publicBranding['icon_url'] }}">
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
