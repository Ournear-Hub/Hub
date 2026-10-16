<?php

namespace App\Support;

class NormalizedWebsiteUrl
{
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\s*javascript\s*:/i', $value) === 1) {
            return null;
        }

        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.ltrim($value, '/');
        }

        $validated = filter_var($value, FILTER_VALIDATE_URL);
        if ($validated === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($validated, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $validated;
    }
}
