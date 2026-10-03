<?php

namespace Tests\Support;

use App\Contracts\WhatsAppGateway;
use App\DataTransferObjects\WhatsAppSendResult;
use App\DataTransferObjects\WhatsAppTemplateMessage;
use App\Exceptions\WhatsAppProviderException;

final class RecordingWhatsAppGateway implements WhatsAppGateway
{
    /** @var list<WhatsAppTemplateMessage> */
    public array $messages = [];

    public ?WhatsAppProviderException $failure = null;

    public int $sequence = 1;

    public function sendTemplate(WhatsAppTemplateMessage $message): WhatsAppSendResult
    {
        $this->messages[] = $message;

        if ($this->failure instanceof WhatsAppProviderException) {
            throw $this->failure;
        }

        $id = 'wamid.test.'.$this->sequence;
        $this->sequence++;

        return new WhatsAppSendResult($id, $message->recipientE164, $message->templateName);
    }

    public function last(): ?WhatsAppTemplateMessage
    {
        if ($this->messages === []) {
            return null;
        }

        return $this->messages[array_key_last($this->messages)];
    }
}
