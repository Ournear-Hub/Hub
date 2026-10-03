<?php

namespace App\Enums;

enum WhatsAppMessagePurpose: string
{
    case Transactional = 'transactional';
    case Authentication = 'authentication';
    case Operational = 'operational';
    case WhatsappVerification = 'whatsapp_verification';
}
