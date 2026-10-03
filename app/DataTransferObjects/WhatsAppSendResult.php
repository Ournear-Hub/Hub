<?php

namespace App\DataTransferObjects;

final class WhatsAppSendResult
{
    public function __construct(
        public readonly string $providerMessageId,
        public readonly string $recipientE164,
        public readonly string $templateName,
    ) {}
}
