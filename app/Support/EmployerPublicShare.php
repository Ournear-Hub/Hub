<?php

namespace App\Support;

class EmployerPublicShare
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            'enabled',
            'about',
            'details',
            'website',
            'industry_size',
            'locations',
            'benefits',
            'socials',
        ];
    }

    /**
     * Existing public company fields stay visible so current profiles keep meaning.
     * New Stage 4 fields stay hidden until the employer opts in.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'about' => true,
            'details' => true,
            'website' => true,
            'industry_size' => true,
            'locations' => false,
            'benefits' => false,
            'socials' => false,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $value
     * @return array<string, bool>
     */
    public static function normalize(?array $value): array
    {
        $share = self::defaults();

        foreach (self::keys() as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $share[$key] = (bool) $value[$key];
            }
        }

        return $share;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function fieldOptions(): array
    {
        return [
            ['key' => 'enabled', 'label' => 'Publish public company page'],
            ['key' => 'about', 'label' => 'Show about company'],
            ['key' => 'details', 'label' => 'Show company details'],
            ['key' => 'website', 'label' => 'Show website'],
            ['key' => 'industry_size', 'label' => 'Show industry and company size'],
            ['key' => 'locations', 'label' => 'Show company locations'],
            ['key' => 'benefits', 'label' => 'Show benefits'],
            ['key' => 'socials', 'label' => 'Show social links'],
        ];
    }
}
