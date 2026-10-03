<?php

namespace App\Contracts;

interface SmsOtpGateway
{
    /**
     * Deliver a one-time code to an E.164 mobile number.
     *
     * Implementations must not log or persist the code except in isolated tests.
     */
    public function send(string $destinationE164, string $code): void;
}
