@if (! empty($publicSeo))
    <meta head-key="description" name="description" content="{{ $publicSeo['description'] }}">
    <link head-key="canonical" rel="canonical" href="{{ $publicSeo['canonical'] }}">
    <meta head-key="robots" name="robots" content="{{ $publicSeo['robots'] }}">
    <meta head-key="og:type" property="og:type" content="{{ $publicSeo['og_type'] }}">
    <meta head-key="og:title" property="og:title" content="{{ $publicSeo['og_title'] }}">
    <meta head-key="og:description" property="og:description" content="{{ $publicSeo['og_description'] }}">
    <meta head-key="og:url" property="og:url" content="{{ $publicSeo['og_url'] }}">
    <meta head-key="og:site_name" property="og:site_name" content="{{ $publicSeo['og_site_name'] }}">
    @if (! empty($publicSeo['og_image']))
        <meta head-key="og:image" property="og:image" content="{{ $publicSeo['og_image'] }}">
    @endif
    <meta head-key="twitter:card" name="twitter:card" content="{{ $publicSeo['twitter_card'] }}">
    <meta head-key="twitter:title" name="twitter:title" content="{{ $publicSeo['twitter_title'] }}">
    <meta head-key="twitter:description" name="twitter:description" content="{{ $publicSeo['twitter_description'] }}">
    @if (! empty($publicSeo['twitter_image']))
        <meta head-key="twitter:image" name="twitter:image" content="{{ $publicSeo['twitter_image'] }}">
    @endif
@endif
@if (! empty($publicAnalytics['enabled']) && ! empty($publicAnalytics['id']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $publicAnalytics['id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($publicAnalytics['id']));
    </script>
@endif
