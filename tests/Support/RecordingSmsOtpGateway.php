<?php

namespace Tests\Support;

use App\Contracts\SmsOtpGateway;

final class RecordingSmsOtpGateway implements SmsOtpGateway
{
    /** @var list<array{destination: string, code: string}> */
    public array $messages = [];

    public function send(string $destinationE164, string $code): void
    {
        $this->messages[] = [
            'destination' => $destinationE164,
            'code' => $code,
        ];
    }

    public function lastCode(): ?string
    {
        if ($this->messages === []) {
            return null;
        }

        return $this->messages[array_key_last($this->messages)]['code'];
    }

    public function lastDestination(): ?string
    {
        if ($this->messages === []) {
            return null;
        }

        return $this->messages[array_key_last($this->messages)]['destination'];
    }
}
