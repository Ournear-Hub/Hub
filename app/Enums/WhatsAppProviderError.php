<?php

namespace App\Enums;

enum WhatsAppProviderError: string
{
    case NotConfigured = 'not_configured';
    case Authentication = 'authentication';
    case InvalidRecipient = 'invalid_recipient';
    case InvalidTemplate = 'invalid_template';
    case TemplateNotApproved = 'template_not_approved';
    case Validation = 'validation';
    case RateLimited = 'rate_limited';
    case Timeout = 'timeout';
    case Temporary = 'temporary';
    case Unknown = 'unknown';
}
