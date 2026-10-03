<?php

namespace App\Support;

final class WhatsAppNumber
{
    /**
     * Normalize a public WhatsApp number to E.164.
     *
     * Empty input returns null. Filled but invalid input returns false so
     * callers can reject it instead of storing an inconsistent format.
     */
    public static function normalize(mixed $value, string $defaultRegion = 'IN'): string|false|null
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $hasPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return false;
        }

        if ($defaultRegion === 'IN') {
            $india = self::normalizeIndia($digits);
            if (is_string($india)) {
                return $india;
            }
        }

        if ($hasPlus && strlen($digits) >= 8 && strlen($digits) <= 15) {
            return '+'.$digits;
        }

        return false;
    }

    public static function waMeUrl(?string $e164): ?string
    {
        if (! is_string($e164) || $e164 === '') {
            return null;
        }

        $digits = ltrim($e164, '+');
        if ($digits === '' || ! ctype_digit($digits)) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

    public static function display(?string $e164): ?string
    {
        if (! is_string($e164) || $e164 === '' || ! str_starts_with($e164, '+')) {
            return null;
        }

        $digits = substr($e164, 1);
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $local = substr($digits, 2);

            return '+91 '.substr($local, 0, 5).' '.substr($local, 5);
        }

        return $e164;
    }

    private static function normalizeIndia(string $digits): ?string
    {
        if (strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits) === 1) {
            return '+91'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $local = substr($digits, 1);
            if (preg_match('/^[6-9]\d{9}$/', $local) === 1) {
                return '+91'.$local;
            }
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $local = substr($digits, 2);
            if (preg_match('/^[6-9]\d{9}$/', $local) === 1) {
                return '+91'.$local;
            }
        }

        return null;
    }

    /**
     * India-only E.164 for Account user WhatsApp identity.
     * Rejects non-Indian numbers that generic normalize() would still accept.
     */
    public static function normalizeIndianIdentity(mixed $value): string|false|null
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return false;
        }

        $india = self::normalizeIndia($digits);

        return is_string($india) ? $india : false;
    }
}
