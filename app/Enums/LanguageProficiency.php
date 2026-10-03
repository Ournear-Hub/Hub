<?php

namespace App\Enums;

enum LanguageProficiency: string
{
    case Basic = 'basic';
    case Conversational = 'conversational';
    case Professional = 'professional';
    case Native = 'native';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Conversational => 'Conversational',
            self::Professional => 'Professional',
            self::Native => 'Native / bilingual',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $proficiency) => [
            'value' => $proficiency->value,
            'label' => $proficiency->label(),
        ], self::cases());
    }
}
