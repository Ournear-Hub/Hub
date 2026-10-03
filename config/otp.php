<?php

return [

    'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 10),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'resend_seconds' => (int) env('OTP_RESEND_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    |
    | Production stays unconfigured until a provider is selected separately.
    | Tests bind a recording gateway. Never put provider secrets here.
    |
    */
    'sms_driver' => env('SMS_OTP_DRIVER', 'unconfigured'),

];
