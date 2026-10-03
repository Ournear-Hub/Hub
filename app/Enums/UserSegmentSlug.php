<?php

namespace App\Enums;

enum UserSegmentSlug: string
{
    case All = 'all';
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
    case Suspended = 'suspended';
    case Verified = 'verified';
    case Unverified = 'unverified';
    case New = 'new';
    case Candidates = 'candidates';
    case Employers = 'employers';
    case Incomplete = 'incomplete';
    case Hyderabad = 'hyderabad';
    case Mining = 'mining';
    case Construction = 'construction';
    case SeekingJobs = 'seeking_jobs';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All Users',
            self::Active => 'Active Users',
            self::Inactive => 'Inactive Users',
            self::Blocked => 'Blocked Users',
            self::Suspended => 'Suspended Users',
            self::Verified => 'Verified Users',
            self::Unverified => 'Unverified Users',
            self::New => 'New Users',
            self::Candidates => 'Candidates',
            self::Employers => 'Employers',
            self::Incomplete => 'Incomplete profiles',
            self::Hyderabad => 'Hyderabad Users',
            self::Mining => 'Mining Professionals',
            self::Construction => 'Construction Professionals',
            self::SeekingJobs => 'Users seeking jobs',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::All => 'Every platform identity on OurNear.',
            self::Active => 'Accounts that can currently sign in.',
            self::Inactive => 'Accounts marked inactive without a block or suspension.',
            self::Blocked => 'Accounts blocked by Admin.',
            self::Suspended => 'Accounts temporarily suspended.',
            self::Verified => 'Users with a verified email address.',
            self::Unverified => 'Users who have not verified email.',
            self::New => 'Registered in the last 30 days.',
            self::Candidates => 'Users with the candidate account type.',
            self::Employers => 'Users with the employer account type.',
            self::Incomplete => 'Profiles below 80% completion.',
            self::Hyderabad => 'Users whose location includes Hyderabad.',
            self::Mining => 'Users linked to the mining industry.',
            self::Construction => 'Users linked to the construction industry.',
            self::SeekingJobs => 'Candidate accounts, including those who have not applied yet.',
        };
    }

    /**
     * Segments shown as primary CRM shortcuts in Admin.
     *
     * @return list<self>
     */
    public static function navigation(): array
    {
        return [
            self::All,
            self::Active,
            self::Inactive,
            self::Blocked,
            self::Suspended,
            self::Verified,
            self::Unverified,
            self::New,
            self::Candidates,
            self::Employers,
        ];
    }
}
