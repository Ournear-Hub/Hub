<?php

namespace App\Services;

use App\Enums\SecurityActivityType;
use App\Enums\UserActivityType;
use App\Models\EmployerProfile;
use App\Models\Location;
use App\Models\User;
use App\Support\EmployerPublicShare;
use App\Support\EmployerSocials;

class EmployerCompanyService
{
    public function __construct(
        private UserActivityService $activities,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateCompany(EmployerProfile $profile, array $attributes, ?User $actor = null, bool $recordActivity = true): EmployerProfile
    {
        $fields = [];

        foreach ([
            'company_name',
            'contact_person',
            'mobile_number',
            'industry',
            'about_company',
            'company_size',
            'company_website',
            'company_details',
        ] as $field) {
            if (array_key_exists($field, $attributes)) {
                $fields[$field] = $attributes[$field];
            }
        }

        if ($fields !== []) {
            $profile->fill($fields)->save();
        }

        if (array_key_exists('location_ids', $attributes) && is_array($attributes['location_ids'])) {
            $this->syncLocations($profile, $attributes['location_ids']);
        }

        if (array_key_exists('benefits', $attributes)) {
            $profile->forceFill([
                'benefits' => $this->normalizeBenefits($attributes['benefits']),
            ])->save();
        }

        if (array_key_exists('socials', $attributes) && is_array($attributes['socials'])) {
            $profile->forceFill([
                'socials' => EmployerSocials::normalize($attributes['socials']),
            ])->save();
        }

        if ($recordActivity) {
            $this->recordProfileUpdate($profile, $actor, 'Company profile details were updated.');
        }

        return $profile->fresh(['locations', 'user', 'classifiedIndustry']) ?? $profile;
    }

    /**
     * @param  array<string, bool>  $share
     */
    public function updatePublicShare(EmployerProfile $profile, array $share, ?User $actor = null): EmployerProfile
    {
        $profile->forceFill([
            'public_share' => EmployerPublicShare::normalize(array_merge($profile->publicShare(), $share)),
        ])->save();

        $this->recordProfileUpdate($profile, $actor, 'Public company sharing was updated.');

        return $profile->fresh(['locations', 'user']) ?? $profile;
    }

    public function togglePostingApproval(EmployerProfile $profile, User $actor): EmployerProfile
    {
        $approved = $profile->job_posting_approved_at === null;

        $profile->forceFill([
            'job_posting_approved_at' => $approved ? now() : null,
            'job_posting_approved_by' => $approved ? $actor->id : null,
        ])->save();

        $user = $profile->user ?? $profile->user()->first();
        if ($user instanceof User) {
            $this->activities->record(
                $user,
                UserActivityType::UpdatedProfile,
                $approved
                    ? 'OurNear approved this employer for job posting.'
                    : 'OurNear revoked job posting approval.',
                $actor,
            );
        }

        $this->audit->record(
            $approved ? SecurityActivityType::EmployerPostingApproved : SecurityActivityType::EmployerPostingRevoked,
            $approved
                ? 'Employer job posting was approved.'
                : 'Employer job posting approval was revoked.',
            $actor,
            $profile,
            ['approved' => $approved],
        );

        return $profile->fresh(['user']) ?? $profile;
    }

    public function setPostingRestricted(EmployerProfile $profile, bool $restricted, User $actor): EmployerProfile
    {
        $currentlyRestricted = $profile->posting_restricted_at !== null;
        if ($currentlyRestricted === $restricted) {
            return $profile;
        }

        $profile->forceFill([
            'posting_restricted_at' => $restricted ? now() : null,
        ])->save();

        $user = $profile->user ?? $profile->user()->first();
        if ($user instanceof User) {
            $this->activities->record(
                $user,
                UserActivityType::UpdatedProfile,
                $restricted
                    ? 'Job posting was restricted by OurNear.'
                    : 'Job posting restriction was removed.',
                $actor,
            );
        }

        $this->audit->record(
            $restricted
                ? SecurityActivityType::EmployerPostingRestricted
                : SecurityActivityType::EmployerPostingUnrestricted,
            $restricted
                ? 'Employer job posting was restricted.'
                : 'Employer job posting restriction was removed.',
            $actor,
            $profile,
            ['restricted' => $restricted],
        );

        return $profile->fresh(['user']) ?? $profile;
    }

    /**
     * @param  list<mixed>  $ids
     */
    private function syncLocations(EmployerProfile $profile, array $ids): void
    {
        $locationIds = Location::query()
            ->whereIn('id', array_values(array_unique(array_filter(
                array_map('intval', $ids),
                fn (int $id) => $id > 0,
            ))))
            ->pluck('id')
            ->all();

        $profile->locations()->sync($locationIds);
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function normalizeBenefits(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $benefits = [];
        $seen = [];

        foreach ($value as $item) {
            $benefit = trim((string) $item);
            if ($benefit === '') {
                continue;
            }

            $key = mb_strtolower($benefit);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $benefits[] = $benefit;

            if (count($benefits) >= 30) {
                break;
            }
        }

        return $benefits;
    }

    private function recordProfileUpdate(EmployerProfile $profile, ?User $actor, string $body): void
    {
        $user = $profile->user ?? $profile->user()->first();
        if (! $user instanceof User) {
            return;
        }

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            $body,
            $actor ?? $user,
        );
    }
}
