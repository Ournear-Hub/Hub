<?php

namespace App\Services\Sms;

use App\Contracts\SmsOtpGateway;
use App\Exceptions\SmsOtpNotConfiguredException;

class UnconfiguredSmsOtpGateway implements SmsOtpGateway
{
    public function send(string $destinationE164, string $code): void
    {
        unset($destinationE164, $code);

        throw SmsOtpNotConfiguredException::make();
    }
}
