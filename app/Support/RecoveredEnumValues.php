<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ContactEnquiryStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\JobPublishingStatus;
use App\Enums\NoticePeriod;
use App\Enums\PreferredLanguage;
use App\Enums\RecruitmentActivityType;
use App\Enums\RegistrationSource;
use App\Enums\UserActivityType;
use App\Enums\UserRole;
use BackedEnum;

/**
 * Maps legacy / recovered database strings onto current backed enum values.
 * Used by {@see \App\Casts\RecoveredBackedEnumCast} and {@see \App\Console\Commands\RepairRecoveredDataCommand}.
 */
final class RecoveredEnumValues
{
    /**
     * @return array<string, string> raw legacy key (often lowercase) => canonical enum value
     */
    public static function legacyAliases(string $enumClass): array
    {
        return match ($enumClass) {
            PreferredLanguage::class => [
                'english' => PreferredLanguage::English->value,
                'telugu' => PreferredLanguage::Telugu->value,
                'hindi' => PreferredLanguage::Hindi->value,
            ],
            RegistrationSource::class => [
                'legacy_recovery' => RegistrationSource::Admin->value,
                'recovery' => RegistrationSource::Admin->value,
                'import' => RegistrationSource::Admin->value,
                'manual' => RegistrationSource::Admin->value,
            ],
            UserRole::class => [
                'staff' => UserRole::Admin->value,
                'recruiter' => UserRole::Admin->value,
            ],
            AccountStatus::class => [
                'active' => AccountStatus::Active->value,
                'inactive' => AccountStatus::Inactive->value,
                'blocked' => AccountStatus::Blocked->value,
                'suspended' => AccountStatus::Suspended->value,
                'pending' => AccountStatus::PendingVerification->value,
                'pending_verification' => AccountStatus::PendingVerification->value,
                'deactivated' => AccountStatus::Deactivated->value,
            ],
            ApplicationStatus::class => [
                'new' => ApplicationStatus::New->value,
                'reviewing' => ApplicationStatus::Reviewing->value,
                'shortlisted' => ApplicationStatus::Shortlisted->value,
                'interview' => ApplicationStatus::Interview->value,
                'selected' => ApplicationStatus::Selected->value,
                'placed' => ApplicationStatus::Placed->value,
                'rejected' => ApplicationStatus::Rejected->value,
                'withdrawn' => ApplicationStatus::Withdrawn->value,
            ],
            JobPublishingStatus::class => [
                'active' => JobPublishingStatus::Published->value,
                'live' => JobPublishingStatus::Published->value,
                'open' => JobPublishingStatus::Published->value,
                'inactive' => JobPublishingStatus::Paused->value,
            ],
            EmployerEnquiryStatus::class => [
                'new' => EmployerEnquiryStatus::New->value,
                'contacted' => EmployerEnquiryStatus::Contacted->value,
                'in progress' => EmployerEnquiryStatus::InProgress->value,
                'in_progress' => EmployerEnquiryStatus::InProgress->value,
                'completed' => EmployerEnquiryStatus::Completed->value,
                'closed' => EmployerEnquiryStatus::Closed->value,
            ],
            UserActivityType::class => [
                'login' => UserActivityType::LoggedIn->value,
                'logout' => UserActivityType::LoggedOut->value,
                'profile_update' => UserActivityType::UpdatedProfile->value,
                'register' => UserActivityType::Registered->value,
            ],
            ContactEnquiryStatus::class => [
                'new' => ContactEnquiryStatus::New->value,
                'contacted' => ContactEnquiryStatus::Contacted->value,
                'completed' => ContactEnquiryStatus::Completed->value,
                'closed' => ContactEnquiryStatus::Closed->value,
            ],
            RecruitmentActivityType::class => [
                'note' => RecruitmentActivityType::NoteAdded->value,
                'status_change' => RecruitmentActivityType::StatusChanged->value,
            ],
            NoticePeriod::class => [
                'immediate' => NoticePeriod::Immediate->value,
                '15 days' => NoticePeriod::Days15->value,
                '15_days' => NoticePeriod::Days15->value,
                '30 days' => NoticePeriod::Days30->value,
                '30_days' => NoticePeriod::Days30->value,
                '60 days' => NoticePeriod::Days60->value,
                '60_days' => NoticePeriod::Days60->value,
                '90 days' => NoticePeriod::Days90->value,
                '90_days' => NoticePeriod::Days90->value,
            ],
            default => [],
        };
    }

    /**
     * Resolve a raw DB string to a canonical backed enum value, or null if unknown.
     */
    public static function normalizeValue(string $enumClass, mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (! is_subclass_of($enumClass, BackedEnum::class)) {
            return $value;
        }

        /** @var class-string<BackedEnum> $enumClass */
        if ($enumClass::tryFrom($value) !== null) {
            return $value;
        }

        foreach ($enumClass::cases() as $case) {
            if (strcasecmp((string) $case->value, $value) === 0) {
                return (string) $case->value;
            }
        }

        $aliases = self::legacyAliases($enumClass);
        $lower = strtolower($value);
        if (isset($aliases[$value])) {
            return $aliases[$value];
        }
        if (isset($aliases[$lower])) {
            return $aliases[$lower];
        }

        return null;
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     */
    public static function cast(string $enumClass, mixed $raw, ?string $context = null): ?BackedEnum
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        $normalized = self::normalizeValue($enumClass, $raw);
        if ($normalized !== null) {
            return $enumClass::from($normalized);
        }

        throw new \ValueError(sprintf(
            '%s is not a valid backing value for enum %s%s',
            is_scalar($raw) ? (string) $raw : get_debug_type($raw),
            $enumClass,
            $context ? " ({$context})" : '',
        ));
    }

    /**
     * @return list<array{table: string, column: string, enum: class-string<BackedEnum>}>
     */
    public static function repairTargets(): array
    {
        return [
            ['table' => 'users', 'column' => 'role', 'enum' => UserRole::class],
            ['table' => 'users', 'column' => 'account_status', 'enum' => AccountStatus::class],
            ['table' => 'users', 'column' => 'preferred_language', 'enum' => PreferredLanguage::class],
            ['table' => 'users', 'column' => 'registration_source', 'enum' => RegistrationSource::class],
            ['table' => 'applications', 'column' => 'status', 'enum' => ApplicationStatus::class],
            ['table' => 'job_listings', 'column' => 'publishing_status', 'enum' => JobPublishingStatus::class],
            ['table' => 'employer_enquiries', 'column' => 'status', 'enum' => EmployerEnquiryStatus::class],
            ['table' => 'contact_enquiries', 'column' => 'status', 'enum' => ContactEnquiryStatus::class],
            ['table' => 'user_activities', 'column' => 'type', 'enum' => UserActivityType::class],
            ['table' => 'recruitment_activities', 'column' => 'type', 'enum' => RecruitmentActivityType::class],
            ['table' => 'candidate_profiles', 'column' => 'notice_period', 'enum' => NoticePeriod::class],
        ];
    }
}
