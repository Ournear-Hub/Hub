<?php

namespace App\Services;

use App\Enums\PreferredLanguage;
use App\Enums\UserActivityType;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class AccountProfileService
{
    public function __construct(
        private UserPhotoService $photos,
        private EmployerLogoService $logos,
        private UserActivityService $activities,
        private EmployerCompanyService $employers,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfile(
        User $user,
        array $attributes,
        ?UploadedFile $photo = null,
        bool $removePhoto = false,
        bool $companyOnly = false,
    ): User {
        if (! $companyOnly) {
            $this->applyPhoto($user, $photo, $removePhoto);

            $user->fill([
                'name' => $attributes['name'] ?? $user->name,
                'username' => $attributes['username'] ?? $user->username,
                'mobile' => array_key_exists('mobile', $attributes) ? $attributes['mobile'] : $user->mobile,
                'location' => array_key_exists('location', $attributes) ? $attributes['location'] : $user->location,
                'bio' => array_key_exists('bio', $attributes) ? $attributes['bio'] : $user->bio,
                'professional_headline' => array_key_exists('professional_headline', $attributes)
                    ? $attributes['professional_headline']
                    : $user->professional_headline,
                'preferred_language' => $attributes['preferred_language'] ?? $user->preferred_language,
            ])->save();
        }

        if ($user->isCandidate() && ! $companyOnly) {
            $candidateAttributes = [
                'mobile' => $user->mobile,
                'location' => $user->location,
            ];

            if (array_key_exists('experience', $attributes)) {
                $candidateAttributes['experience'] = $attributes['experience'];
            }

            if (array_key_exists('qualification', $attributes)) {
                $candidateAttributes['qualification'] = $attributes['qualification'];
            }

            $user->candidateProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $candidateAttributes,
            );
        }

        if ($companyOnly && array_key_exists('whatsapp_number', $attributes)) {
            $user->fill(['whatsapp_number' => $attributes['whatsapp_number']])->save();
        }

        if ($user->canAccessHiringWorkspace() && ($companyOnly || $this->hasEmployerProfileAttributes($attributes))) {
            $employerScalars = [];
            foreach ([
                'company_name',
                'contact_person',
                'mobile_number',
                'industry',
                'industry_id',
                'about_company',
                'company_size',
                'company_website',
                'company_details',
            ] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $employerScalars[$field] = $attributes[$field];
                }
            }

            $foundation = [];
            if (array_key_exists('location_ids', $attributes)) {
                $foundation['location_ids'] = $attributes['location_ids'];
            }
            if (array_key_exists('benefits', $attributes)) {
                $foundation['benefits'] = $attributes['benefits'];
            }
            if (array_key_exists('socials', $attributes)) {
                $foundation['socials'] = $attributes['socials'];
            }

            if ($employerScalars !== [] || $foundation !== []) {
                $profile = $user->employerProfile()->firstOrCreate(['user_id' => $user->id]);

                if (array_key_exists('industry_id', $employerScalars)) {
                    $industryId = $employerScalars['industry_id'];
                    if ($industryId) {
                        $employerScalars['industry'] = Industry::query()->whereKey($industryId)->value('name');
                    }
                }

                if ($employerScalars !== []) {
                    $profile->fill($employerScalars)->save();
                }

                if ($foundation !== []) {
                    $this->employers->updateCompany($profile, $foundation, $user, false);
                }
            }
        }

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            $companyOnly ? 'Company profile details were updated.' : 'Profile details were updated.',
            $user,
        );

        return $user->fresh(['candidateProfile', 'employerProfile']) ?? $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function hasEmployerProfileAttributes(array $attributes): bool
    {
        foreach ([
            'company_name',
            'contact_person',
            'mobile_number',
            'industry',
            'industry_id',
            'about_company',
            'company_size',
            'company_website',
            'company_details',
            'location_ids',
            'benefits',
            'socials',
        ] as $field) {
            if (array_key_exists($field, $attributes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAccount(User $user, array $attributes): User
    {
        $user->fill([
            'username' => $attributes['username'] ?? $user->username,
            'mobile' => array_key_exists('mobile', $attributes) ? $attributes['mobile'] : $user->mobile,
            'whatsapp_number' => array_key_exists('whatsapp_number', $attributes) ? $attributes['whatsapp_number'] : $user->whatsapp_number,
            'preferred_language' => $attributes['preferred_language'] ?? $user->preferred_language,
        ])->save();

        if ($user->isCandidate()) {
            $user->candidateProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'mobile' => $user->mobile,
                    'location' => $user->location,
                ],
            );
        }

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            'Account settings were updated.',
            $user,
        );

        return $user->fresh(['candidateProfile', 'employerProfile']) ?? $user;
    }

    public function updatePhoto(User $user, UploadedFile $photo): User
    {
        $this->applyPhoto($user, $photo, false);
        $user->save();

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            'Profile photo was updated.',
            $user,
        );

        return $user->fresh() ?? $user;
    }

    public function removePhoto(User $user): User
    {
        $this->applyPhoto($user, null, true);
        $user->save();

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            'Profile photo was removed.',
            $user,
        );

        return $user->fresh() ?? $user;
    }

    public function updateCompanyLogo(User $user, UploadedFile $logo): User
    {
        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        abort_unless($profile instanceof \App\Models\EmployerProfile, 403);

        $previous = $profile->logo_path;
        $profile->logo_path = $this->logos->store($logo);
        $profile->save();
        $this->logos->delete($previous);

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            'Company logo was updated.',
            $user,
        );

        return $user->fresh(['employerProfile']) ?? $user;
    }

    public function removeCompanyLogo(User $user): User
    {
        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        abort_unless($profile instanceof \App\Models\EmployerProfile, 403);

        $this->logos->delete($profile->logo_path);
        $profile->logo_path = null;
        $profile->save();

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            'Company logo was removed.',
            $user,
        );

        return $user->fresh(['employerProfile']) ?? $user;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function languageOptions(): array
    {
        return array_map(fn (PreferredLanguage $language) => [
            'value' => $language->value,
            'label' => $language->label(),
        ], PreferredLanguage::cases());
    }

    private function applyPhoto(User $user, ?UploadedFile $photo, bool $removePhoto): void
    {
        if ($photo) {
            $previous = $user->photo_path;
            $user->photo_path = $this->photos->store($photo);
            $this->photos->delete($previous);

            return;
        }

        if ($removePhoto) {
            $this->photos->delete($user->photo_path);
            $user->photo_path = null;
        }
    }
}
