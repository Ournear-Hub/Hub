<?php

namespace App\Models;

use App\Enums\WhatsAppBusinessIdentity;
use App\Enums\WhatsAppDeliveryStatus;
use App\Enums\WhatsAppMessagePurpose;
use Illuminate\Database\Eloquent\Model;

class WhatsAppOutboundMessage extends Model
{
    protected $table = 'whatsapp_outbound_messages';
    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider_message_id',
        'purpose',
        'identity',
        'recipient',
        'template_name',
        'language',
        'status',
        'company_sender',
        'provider_error_code',
        'last_status_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => WhatsAppMessagePurpose::class,
            'identity' => WhatsAppBusinessIdentity::class,
            'status' => WhatsAppDeliveryStatus::class,
            'last_status_at' => 'datetime',
            'provider_error_code' => 'integer',
        ];
    }
}
