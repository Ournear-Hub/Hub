<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use App\DataTransferObjects\WhatsAppSendResult;
use App\DataTransferObjects\WhatsAppTemplateMessage;
use App\Enums\WhatsAppProviderError;
use App\Exceptions\WhatsAppProviderException;
use App\Support\WhatsAppCloudConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppCloudGateway implements WhatsAppGateway
{
    public function sendTemplate(WhatsAppTemplateMessage $message): WhatsAppSendResult
    {
        if (! WhatsAppCloudConfig::isReady()) {
            throw WhatsAppProviderException::notConfigured();
        }

        $to = ltrim($message->recipientE164, '+');

        try {
            $response = Http::timeout(WhatsAppCloudConfig::timeoutSeconds())
                ->acceptJson()
                ->withToken(WhatsAppCloudConfig::accessToken())
                ->post(WhatsAppCloudConfig::messagesEndpoint(), $this->payload($message, $to));
        } catch (ConnectionException $exception) {
            unset($exception);

            throw new WhatsAppProviderException(
                'WhatsApp Cloud API timed out.',
                WhatsAppProviderError::Timeout,
            );
        }

        if ($response->successful()) {
            $id = (string) data_get($response->json(), 'messages.0.id', '');
            if ($id === '') {
                throw new WhatsAppProviderException(
                    'WhatsApp Cloud API did not return a message id.',
                    WhatsAppProviderError::Unknown,
                );
            }

            Log::info('WhatsApp template accepted by provider.', [
                'template' => $message->templateName,
                'purpose' => $message->purpose,
                'identity' => $message->identity,
            ]);

            return new WhatsAppSendResult($id, $message->recipientE164, $message->templateName);
        }

        throw $this->exceptionFromResponse(
            $response->status(),
            is_array($response->json()) ? $response->json() : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(WhatsAppTemplateMessage $message, string $to): array
    {
        $template = [
            'name' => $message->templateName,
            'language' => ['code' => $message->language],
        ];

        $components = [];
        if ($message->bodyParameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $text): array => ['type' => 'text', 'text' => $text],
                    $message->bodyParameters,
                ),
            ];
        }

        if (is_string($message->urlButtonText) && $message->urlButtonText !== '') {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [[
                    'type' => 'text',
                    'text' => $message->urlButtonText,
                ]],
            ];
        }

        if ($components !== []) {
            $template['components'] = $components;
        }

        return [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => $template,
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function exceptionFromResponse(int $httpStatus, array $json): WhatsAppProviderException
    {
        $code = isset($json['error']['code']) ? (int) $json['error']['code'] : null;
        $subcode = isset($json['error']['error_subcode']) ? (int) $json['error']['error_subcode'] : null;
        $error = $this->classify($httpStatus, $code, $subcode);

        Log::warning('WhatsApp Cloud API request failed.', [
            'http_status' => $httpStatus,
            'provider_code' => $code,
            'error' => $error->value,
        ]);

        return new WhatsAppProviderException(
            $this->safeMessage($error),
            $error,
            $code,
        );
    }

    private function classify(int $httpStatus, ?int $code, ?int $subcode): WhatsAppProviderError
    {
        if (in_array($httpStatus, [401, 403], true) || in_array($code, [190, 102, 10], true)) {
            return WhatsAppProviderError::Authentication;
        }

        if ($httpStatus === 429 || in_array($code, [4, 80007, 130429], true)) {
            return WhatsAppProviderError::RateLimited;
        }

        if (in_array($code, [131026, 131047], true)) {
            return WhatsAppProviderError::InvalidRecipient;
        }

        if (in_array($code, [132000, 132001, 132005, 132007, 132012, 132015, 132016], true)
            || in_array($subcode, [2494010, 2494055], true)) {
            return WhatsAppProviderError::InvalidTemplate;
        }

        if (in_array($code, [132001, 133010], true)) {
            return WhatsAppProviderError::TemplateNotApproved;
        }

        if ($httpStatus >= 400 && $httpStatus < 500) {
            return WhatsAppProviderError::Validation;
        }

        if ($httpStatus >= 500) {
            return WhatsAppProviderError::Temporary;
        }

        return WhatsAppProviderError::Unknown;
    }

    private function safeMessage(WhatsAppProviderError $error): string
    {
        return match ($error) {
            WhatsAppProviderError::Authentication => 'WhatsApp Cloud API authentication failed.',
            WhatsAppProviderError::InvalidRecipient => 'That WhatsApp number could not be used.',
            WhatsAppProviderError::InvalidTemplate,
            WhatsAppProviderError::TemplateNotApproved => 'The WhatsApp template is not available.',
            WhatsAppProviderError::Validation => 'The WhatsApp message could not be accepted.',
            WhatsAppProviderError::RateLimited => 'WhatsApp Cloud API rate limit reached. Try again later.',
            WhatsAppProviderError::Timeout => 'WhatsApp Cloud API timed out.',
            WhatsAppProviderError::Temporary => 'WhatsApp Cloud API is temporarily unavailable.',
            WhatsAppProviderError::NotConfigured => 'WhatsApp Cloud API is not configured yet.',
            WhatsAppProviderError::Unknown => 'The WhatsApp message could not be sent.',
        };
    }
}
