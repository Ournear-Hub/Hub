<?php

namespace App\Support;

use App\Models\User;

final class UserWhatsAppNumber
{
    public static function normalize(mixed $value): string|false|null
    {
        return WhatsAppNumber::normalizeIndianIdentity($value);
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
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'whatsapp_number')) {
            return false;
        }

        return User::query()
            ->whereNotNull('whatsapp_number')
            ->when($ignoreUserId !== null, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->get(['id', 'whatsapp_number'])
            ->contains(function (User $user) use ($normalized): bool {
                $existing = self::normalize($user->whatsapp_number);

                return is_string($existing) && $existing === $normalized;
            });
    }
}
