<?php

namespace App\Contracts;

use App\DataTransferObjects\WhatsAppSendResult;
use App\DataTransferObjects\WhatsAppTemplateMessage;

interface WhatsAppGateway
{
    public function sendTemplate(WhatsAppTemplateMessage $message): WhatsAppSendResult;
}
