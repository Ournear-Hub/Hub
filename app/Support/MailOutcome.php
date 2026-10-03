<?php

namespace App\Support;

class MailOutcome
{
    public function __construct(public readonly bool $confirmationDelivered) {}

    public function publicMessage(string $base, string $confirmationClause): string
    {
        if (! $this->confirmationDelivered) {
            return $base;
        }

        return $base.' '.$confirmationClause;
    }
}
