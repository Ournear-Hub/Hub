<?php

namespace App\Enums;

enum JobPublishingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Paused = 'paused';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Paused => 'Paused',
            self::Closed => 'Closed',
            self::Archived => 'Archived',
        };
    }

    public function isListedPublicly(): bool
    {
        return $this === self::Published;
    }

    /**
     * @return list<self>
     */
    public static function hiddenFromPublic(): array
    {
        return [self::Draft, self::Paused, self::Closed, self::Archived];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
