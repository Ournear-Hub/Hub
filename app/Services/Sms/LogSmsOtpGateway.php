<?php

namespace App\Services\Sms;

use App\Contracts\SmsOtpGateway;
use Illuminate\Support\Facades\Log;

/**
 * Local development SMS driver: writes OTP payloads to the application log.
 * Never use in production.
 */
class LogSmsOtpGateway implements SmsOtpGateway
{
    public function send(string $destinationE164, string $code): void
    {
        Log::info('SMS OTP (local log driver)', [
            'destination' => $destinationE164,
            'code' => $code,
        ]);
    }
}
