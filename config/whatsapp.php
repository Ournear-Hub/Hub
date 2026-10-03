<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API
    |--------------------------------------------------------------------------
    |
    | Official Meta WhatsApp Business Platform only. Secrets stay in the
    | environment. Company click-to-chat numbers stay in Admin Website
    | Settings (Stage 23B). Default provider is unconfigured so the app
    | boots without live Meta credentials.
    |
    */

    'provider' => env('WHATSAPP_PROVIDER', 'unconfigured'),

    'graph' => [
        'base_url' => env('WHATSAPP_GRAPH_BASE_URL', 'https://graph.facebook.com'),
        'version' => env('WHATSAPP_GRAPH_API_VERSION', ''),
        'timeout' => (int) env('WHATSAPP_GRAPH_TIMEOUT', 15),
    ],

    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),

    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),

    'app_secret' => env('WHATSAPP_APP_SECRET', ''),

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', ''),

    'otp' => [
        'template_name' => env('WHATSAPP_OTP_TEMPLATE_NAME', ''),
        'template_language' => env('WHATSAPP_OTP_TEMPLATE_LANGUAGE', 'en'),
        'include_copy_button' => filter_var(env('WHATSAPP_OTP_INCLUDE_COPY_BUTTON', true), FILTER_VALIDATE_BOOL),
    ],

];
