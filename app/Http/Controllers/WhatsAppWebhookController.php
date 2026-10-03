<?php

namespace App\Http\Controllers;

use App\Enums\WhatsAppDeliveryStatus;
use App\Services\WhatsAppMessagingService;
use App\Support\WhatsAppCloudConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $expected = WhatsAppCloudConfig::verifyToken();
        if ($expected === '' || $mode !== 'subscribe' || ! hash_equals($expected, $token)) {
            abort(403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function callback(Request $request, WhatsAppMessagingService $messages): Response
    {
        if (! $this->signatureIsValid($request)) {
            abort(403);
        }

        $payload = $request->all();
        if (! is_array($payload)) {
            return response()->noContent();
        }

        foreach ($this->statusesFromPayload($payload) as $status) {
            $messages->applyStatus(
                $status['id'],
                $status['status'],
                $status['error_code'],
            );
        }

        return response()->noContent();
    }

    private function signatureIsValid(Request $request): bool
    {
        $secret = WhatsAppCloudConfig::appSecret();
        if ($secret === '') {
            Log::notice('WhatsApp webhook rejected because the app secret is not configured.');

            return false;
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{id: string, status: WhatsAppDeliveryStatus, error_code: int|null}>
     */
    private function statusesFromPayload(array $payload): array
    {
        $found = [];
        $entries = $payload['entry'] ?? [];
        if (! is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {
            $changes = is_array($entry) ? ($entry['changes'] ?? []) : [];
            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                $value = is_array($change) ? ($change['value'] ?? []) : [];
                $statuses = is_array($value) ? ($value['statuses'] ?? []) : [];
                if (! is_array($statuses)) {
                    continue;
                }

                foreach ($statuses as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $id = trim((string) ($item['id'] ?? ''));
                    $mapped = $this->mapStatus((string) ($item['status'] ?? ''));
                    if ($id === '' || $mapped === null) {
                        continue;
                    }

                    $errorCode = data_get($item, 'errors.0.code');

                    $found[] = [
                        'id' => $id,
                        'status' => $mapped,
                        'error_code' => is_numeric($errorCode) ? (int) $errorCode : null,
                    ];
                }
            }
        }

        return $found;
    }

    private function mapStatus(string $status): ?WhatsAppDeliveryStatus
    {
        return match (strtolower(trim($status))) {
            'sent' => WhatsAppDeliveryStatus::Sent,
            'delivered' => WhatsAppDeliveryStatus::Delivered,
            'read' => WhatsAppDeliveryStatus::Read,
            'failed' => WhatsAppDeliveryStatus::Failed,
            default => null,
        };
    }
}
