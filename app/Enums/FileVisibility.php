<?php

namespace App\Enums;

enum FileVisibility: string
{
    case Private = 'private';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Shared => 'Shared with recruitment staff',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $visibility) => [
            'value' => $visibility->value,
            'label' => $visibility->label(),
        ], self::cases());
    }
}
