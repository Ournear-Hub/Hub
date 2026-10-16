<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public share link base URL
    |--------------------------------------------------------------------------
    |
    | Canonical domain for links copied or sent via WhatsApp, email, and native
    | share. Recipients should resolve to the public OurNear Hire site (e.g.
    | https://ournear.app). Override in local .env when testing without DNS.
    |
    */

    'public_base_url' => env('PUBLIC_SHARE_BASE_URL', 'https://ournear.app'),

];
