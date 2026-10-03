<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use App\DataTransferObjects\WhatsAppSendResult;
use App\DataTransferObjects\WhatsAppTemplateMessage;
use App\Exceptions\WhatsAppProviderException;

class UnconfiguredWhatsAppGateway implements WhatsAppGateway
{
    public function sendTemplate(WhatsAppTemplateMessage $message): WhatsAppSendResult
    {
        unset($message);

        throw WhatsAppProviderException::notConfigured();
    }
}
