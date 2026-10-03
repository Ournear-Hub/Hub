<?php

namespace App\Support;

class CandidatePublicShare
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            'enabled',
            'bio',
            'headline',
            'location',
            'current_role',
            'skills',
            'history',
            'education',
            'projects',
            'certifications',
            'languages',
        ];
    }

    /**
     * Conservative defaults: the public URL works, but only name/username/photo are shown
     * until the candidate opts into additional fields.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'bio' => false,
            'headline' => false,
            'location' => false,
            'current_role' => false,
            'skills' => false,
            'history' => false,
            'education' => false,
            'projects' => false,
            'certifications' => false,
            'languages' => false,
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
            ['key' => 'enabled', 'label' => 'Publish public profile'],
            ['key' => 'bio', 'label' => 'Show bio'],
            ['key' => 'headline', 'label' => 'Show professional headline'],
            ['key' => 'location', 'label' => 'Show location'],
            ['key' => 'current_role', 'label' => 'Show current role and company'],
            ['key' => 'skills', 'label' => 'Show skills'],
            ['key' => 'history', 'label' => 'Show employment history'],
            ['key' => 'education', 'label' => 'Show education'],
            ['key' => 'projects', 'label' => 'Show projects'],
            ['key' => 'certifications', 'label' => 'Show certifications'],
            ['key' => 'languages', 'label' => 'Show languages'],
        ];
    }
}
