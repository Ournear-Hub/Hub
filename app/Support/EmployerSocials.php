<?php

namespace App\Support;

class EmployerSocials
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            'linkedin',
            'facebook',
            'twitter',
            'instagram',
            'youtube',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function defaults(): array
    {
        return array_fill_keys(self::keys(), null);
    }

    /**
     * @param  array<string, mixed>|null  $value
     * @return array<string, string|null>
     */
    public static function normalize(?array $value): array
    {
        $socials = self::defaults();

        foreach (self::keys() as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                continue;
            }

            $item = trim((string) $value[$key]);
            $socials[$key] = $item === '' ? null : $item;
        }

        return $socials;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function fieldOptions(): array
    {
        return [
            ['key' => 'linkedin', 'label' => 'LinkedIn'],
            ['key' => 'facebook', 'label' => 'Facebook'],
            ['key' => 'twitter', 'label' => 'X / Twitter'],
            ['key' => 'instagram', 'label' => 'Instagram'],
            ['key' => 'youtube', 'label' => 'YouTube'],
        ];
    }
}
