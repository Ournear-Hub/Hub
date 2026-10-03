<?php

namespace App\Services;

use App\Contracts\WhatsAppGateway;
use App\DataTransferObjects\WhatsAppTemplateMessage;
use App\Enums\WhatsAppBusinessIdentity;
use App\Enums\WhatsAppDeliveryStatus;
use App\Enums\WhatsAppMessagePurpose;
use App\Enums\WhatsAppProviderError;
use App\Exceptions\WhatsAppProviderException;
use App\Models\WhatsAppOutboundMessage;
use App\Support\WhatsAppNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WhatsAppMessagingService
{
    public function __construct(
        private WhatsAppGateway $gateway,
        private WebsiteSettingService $settings,
    ) {}

    /**
     * @param  list<string>  $bodyParameters
     */
    public function sendTemplate(
        string $recipient,
        string $templateName,
        string $language = 'en',
        array $bodyParameters = [],
        WhatsAppMessagePurpose $purpose = WhatsAppMessagePurpose::Transactional,
        WhatsAppBusinessIdentity $identity = WhatsAppBusinessIdentity::Primary,
        ?string $urlButtonText = null,
    ): WhatsAppOutboundMessage {
        $recipientE164 = $this->normalizeRecipient($recipient);
        $templateName = $this->assertTemplateName($templateName);
        $language = $this->assertLanguage($language);
        $parameters = $this->assertBodyParameters($bodyParameters);
        $companySender = $this->companySender($identity);

        $message = new WhatsAppTemplateMessage(
            recipientE164: $recipientE164,
            templateName: $templateName,
            language: $language,
            bodyParameters: $parameters,
            purpose: $purpose->value,
            identity: $identity->value,
            companySenderE164: $companySender,
            urlButtonText: $urlButtonText,
        );

        try {
            $result = $this->gateway->sendTemplate($message);
        } catch (WhatsAppProviderException $exception) {
            Log::notice('WhatsApp template send did not complete.', [
                'error' => $exception->error->value,
                'template' => $templateName,
                'purpose' => $purpose->value,
            ]);

            throw $exception;
        }

        return WhatsAppOutboundMessage::query()->create([
            'provider_message_id' => $result->providerMessageId,
            'purpose' => $purpose,
            'identity' => $identity,
            'recipient' => $recipientE164,
            'template_name' => $templateName,
            'language' => $language,
            'status' => WhatsAppDeliveryStatus::Accepted,
            'company_sender' => $companySender,
            'last_status_at' => now(),
        ]);
    }

    public function applyStatus(
        string $providerMessageId,
        WhatsAppDeliveryStatus $status,
        ?int $providerErrorCode = null,
        ?\DateTimeInterface $at = null,
    ): ?WhatsAppOutboundMessage {
        $providerMessageId = trim($providerMessageId);
        if ($providerMessageId === '') {
            return null;
        }

        $row = WhatsAppOutboundMessage::query()
            ->where('provider_message_id', $providerMessageId)
            ->first();

        if ($row === null) {
            return null;
        }

        $current = $row->status instanceof WhatsAppDeliveryStatus
            ? $row->status
            : WhatsAppDeliveryStatus::tryFrom((string) $row->status);

        if (! $current instanceof WhatsAppDeliveryStatus) {
            return $row;
        }

        if (! $current->canTransitionTo($status)) {
            return $row;
        }

        $row->forceFill([
            'status' => $status,
            'provider_error_code' => $status === WhatsAppDeliveryStatus::Failed
                ? $providerErrorCode
                : $row->provider_error_code,
            'last_status_at' => $at ?? now(),
        ])->save();

        return $row->fresh();
    }

    private function normalizeRecipient(string $recipient): string
    {
        $normalized = WhatsAppNumber::normalize($recipient);
        if (! is_string($normalized)) {
            throw new WhatsAppProviderException(
                'Enter a valid recipient WhatsApp number.',
                WhatsAppProviderError::InvalidRecipient,
            );
        }

        return $normalized;
    }

    private function companySender(WhatsAppBusinessIdentity $identity): string
    {
        $number = $identity === WhatsAppBusinessIdentity::Recruitment
            ? $this->settings->recruitmentWhatsapp()
            : $this->settings->primaryWhatsapp();

        if (! is_string($number) || $number === '') {
            throw ValidationException::withMessages([
                'whatsapp' => 'Set the company WhatsApp number in Website Settings before sending.',
            ]);
        }

        return $number;
    }

    private function assertTemplateName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 128 || ! preg_match('/^[a-z0-9_]+$/', $name)) {
            throw ValidationException::withMessages([
                'template' => 'Enter a valid WhatsApp template name.',
            ]);
        }

        return $name;
    }

    private function assertLanguage(string $language): string
    {
        $language = trim($language);
        if ($language === '' || strlen($language) > 16 || ! preg_match('/^[A-Za-z0-9_\-]+$/', $language)) {
            throw ValidationException::withMessages([
                'language' => 'Enter a valid WhatsApp template language code.',
            ]);
        }

        return $language;
    }

    /**
     * @param  list<mixed>  $parameters
     * @return list<string>
     */
    private function assertBodyParameters(array $parameters): array
    {
        $clean = [];
        foreach ($parameters as $parameter) {
            if (! is_string($parameter) && ! is_int($parameter) && ! is_float($parameter)) {
                throw ValidationException::withMessages([
                    'parameters' => 'WhatsApp template parameters must be text.',
                ]);
            }

            $value = trim((string) $parameter);
            if ($value === '' || strlen($value) > 1024) {
                throw ValidationException::withMessages([
                    'parameters' => 'WhatsApp template parameters must be short text values.',
                ]);
            }

            $clean[] = $value;
        }

        return $clean;
    }
}
