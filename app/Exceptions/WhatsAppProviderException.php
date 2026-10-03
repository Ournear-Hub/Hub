<?php

namespace App\Exceptions;

use App\Enums\WhatsAppProviderError;
use RuntimeException;

class WhatsAppProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly WhatsAppProviderError $error,
        public readonly ?int $providerCode = null,
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(): self
    {
        return new self(
            'WhatsApp Cloud API is not configured yet.',
            WhatsAppProviderError::NotConfigured,
        );
    }
}
