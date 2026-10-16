<?php

namespace App\Enums;

enum ShareChannel: string
{
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Copy = 'copy';
    case Native = 'native';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::Copy => 'Copy link',
            self::Native => 'Native share',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $channel) => ['value' => $channel->value, 'label' => $channel->label()],
            self::cases(),
        );
    }
}
