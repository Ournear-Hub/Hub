<?php

namespace App\Support;

use App\Models\User;

final class MobileNumber
{
    public static function normalize(mixed $value, string $defaultRegion = 'IN'): string|false|null
    {
        return WhatsAppNumber::normalize($value, $defaultRegion);
    }

    public static function display(?string $value): ?string
    {
        $normalized = self::normalize($value);
        if (! is_string($normalized)) {
            return is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return WhatsAppNumber::display($normalized);
    }

    public static function same(?string $left, ?string $right): bool
    {
        $a = self::normalize($left);
        $b = self::normalize($right);

        if (is_string($a) && is_string($b)) {
            return $a === $b;
        }

        $leftTrim = is_string($left) ? trim($left) : '';
        $rightTrim = is_string($right) ? trim($right) : '';

        if ($leftTrim === '' && $rightTrim === '') {
            return true;
        }

        return $leftTrim !== '' && $leftTrim === $rightTrim;
    }

    public static function takenByAnotherUser(string $normalized, ?int $ignoreUserId): bool
    {
        return User::query()
            ->whereNotNull('mobile')
            ->when($ignoreUserId !== null, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->get(['id', 'mobile'])
            ->contains(function (User $user) use ($normalized): bool {
                $existing = self::normalize($user->mobile);

                return is_string($existing) && $existing === $normalized;
            });
    }
}
