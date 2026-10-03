<?php

namespace App\DataTransferObjects;

final class WhatsAppTemplateMessage
{
    /**
     * @param  list<string>  $bodyParameters
     */
    public function __construct(
        public readonly string $recipientE164,
        public readonly string $templateName,
        public readonly string $language,
        public readonly array $bodyParameters,
        public readonly string $purpose,
        public readonly string $identity,
        public readonly ?string $companySenderE164,
        public readonly ?string $urlButtonText = null,
    ) {}
}
