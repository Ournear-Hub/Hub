<?php

namespace App\Enums;

enum FreelancerGender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Other => 'Other',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $gender) => [
            'value' => $gender->value,
            'label' => $gender->label(),
        ], self::cases());
    }
}
