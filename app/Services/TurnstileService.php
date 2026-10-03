<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class TurnstileService
{
    public function isEnabled(): bool
    {
        return filled($this->siteKey()) && filled($this->secretKey());
    }

    public function siteKey(): ?string
    {
        $key = config('services.turnstile.site_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function validate(Request $request, Validator $validator): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $token = trim((string) $request->input('cf-turnstile-response', ''));
        if ($token === '') {
            $validator->errors()->add('cf-turnstile-response', 'Please confirm you are not a robot and try again.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $this->secretKey(),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Turnstile verification request failed.', ['message' => $exception->getMessage()]);
            $validator->errors()->add('cf-turnstile-response', 'Please confirm you are not a robot and try again.');

            return;
        }

        $success = (bool) $response->json('success');
        if (! $success) {
            $validator->errors()->add('cf-turnstile-response', 'Please confirm you are not a robot and try again.');
        }
    }

    private function secretKey(): ?string
    {
        $key = config('services.turnstile.secret_key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
