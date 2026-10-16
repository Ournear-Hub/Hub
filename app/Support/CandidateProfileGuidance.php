<?php

namespace App\Support;

use App\Models\User;

class CandidateProfileGuidance
{
    /** @var list<string> */
    private const RESUME_RELEVANT_KEYS = [
        'name',
        'bio',
        'location',
        'mobile',
        'current_role',
        'experience',
        'industry_or_function',
        'skills',
        'employment_history',
    ];

    /**
     * @return array{title: string, description: string, href: string, cta: string}
     */
    public static function resolveNextAction(User $user, bool $hasPrimaryResume): array
    {
        $completion = $user->calculateProfileCompletion();
        $items = $user->profileCompletionItems();

        if ($completion < 100) {
            return [
                'title' => 'Complete Your Personal & Professional Information',
                'description' => 'Complete your personal profile and professional details to improve your chances of being shortlisted for suitable job opportunities. Accurate, complete information helps recruiters assess your qualifications and experience more effectively. Completion does not guarantee shortlisting or hire.',
                'href' => '/account/profile#personal',
                'cta' => 'Complete Your Profile',
            ];
        }

        if (! $hasPrimaryResume) {
            return [
                'title' => 'Upload your resume',
                'description' => 'Add your CV for roles that accept a file upload alongside your profile.',
                'href' => '/account/resumes',
                'cta' => 'Manage resumes',
            ];
        }

        if (! self::isHireResumeReady($items)) {
            $incomplete = collect($items)->first(
                fn (array $item) => ! $item['complete'] && self::isResumeRelevantKey($item['key']),
            );
            if ($incomplete !== null) {
                return [
                    'title' => 'Complete profile for OurNear Resume',
                    'description' => 'OurNear Resume pulls from your profile—fill any missing sections to mark it ready.',
                    'href' => self::hrefForCompletionKey($incomplete['key']),
                    'cta' => 'Complete profile',
                ];
            }
        }

        return [
            'title' => 'Find your next opportunity',
            'description' => 'Browse open roles and apply with your profile and resume.',
            'href' => '/jobs',
            'cta' => 'Browse jobs',
        ];
    }

    /**
     * @param  list<array{key: string, label: string, complete: bool, points: int}>  $items
     */
    public static function isHireResumeReady(array $items): bool
    {
        $relevant = array_values(array_filter(
            $items,
            fn (array $item) => self::isResumeRelevantKey($item['key']),
        ));

        if ($relevant === []) {
            return true;
        }

        foreach ($relevant as $item) {
            if (! $item['complete']) {
                return false;
            }
        }

        return true;
    }

    public static function hrefForCompletionKey(string $key): string
    {
        return match ($key) {
            'name', 'location', 'mobile', 'username', 'photo' => '/account/profile#basic',
            'bio' => '/account/profile#about',
            'current_role', 'experience', 'industry_or_function' => '/account/profile#professional',
            'skills' => '/account/profile#skills',
            'employment_history' => '/account/profile#experience',
            default => '/account/profile',
        };
    }

    /**
     * @param  array{key: string, label: string, complete: bool, points: int}  $item
     * @return array{title: string, description: string, href: string, cta: string}
     */
    private static function actionForScoredItem(array $item): array
    {
        return [
            'title' => 'Complete: '.$item['label'],
            'description' => 'Finish this section to improve your profile completion score.',
            'href' => self::hrefForCompletionKey($item['key']),
            'cta' => 'Continue profile',
        ];
    }

    private static function isResumeRelevantKey(string $key): bool
    {
        return in_array($key, self::RESUME_RELEVANT_KEYS, true);
    }
}
