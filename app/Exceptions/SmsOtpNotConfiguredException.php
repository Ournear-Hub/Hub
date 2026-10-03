<?php

namespace App\Exceptions;

use RuntimeException;

class SmsOtpNotConfiguredException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Mobile verification SMS is not configured yet. Please try again after SMS delivery is enabled.');
    }
}
