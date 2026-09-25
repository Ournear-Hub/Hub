<?php

namespace App\Services;

use App\Enums\PreferredLanguage;
use App\Enums\UserActivityType;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class AccountProfileService
{
    public function __construct(
        private UserPhotoService $photos,
        private UserActivityService $activities,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfile(User $user, array $attributes, ?UploadedFile $photo = null, bool $removePhoto = false): User
    {
        $this->applyPhoto($user, $photo, $removePhoto);

        $user->fill([
            'name' => $attributes['name'] ?? $user->name,
            'username' => $attributes['username'] ?? $user->username,
            'mobile' => $attributes['mobile'] ?? $user->mobile,
            'location' => $attributes['location'] ?? $user->location,
            'bio' => array_key_exists('bio', $attributes) ? $attributes['bio'] : $user->bio,
            'preferred_language' => $attributes['preferred_language'] ?? $user->preferred_language,
        ])->save();

        if ($user->isCandidate()) {
            $user->candidateProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'mobile' => $user->mobile,
                    'location' => $user->location,
                    'experience' => $attributes['experience'] ?? $user->candidateProfile?->experience,
                    'qualification' => $attributes['qualification'] ?? $user->candidateProfile?->qualification,
                ],
            );
        }

        if ($user->isEmployer()) {
            $user->employerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $attributes['company_name'] ?? $user->employerProfile?->company_name,
                    'contact_person' => $attributes['contact_person'] ?? $user->employerProfile?->contact_person,
                    'mobile_number' => $attributes['mobile_number'] ?? $user->employerProfile?->mobile_number,
                    'industry' => $attributes['industry'] ?? $user->employerProfile?->industry,
                    'about_company' => $attributes['about_company'] ?? $user->employerProfile?->about_company,
                    'company_size' => $attributes['company_size'] ?? $user->employerProfile?->company_size,
                    'company_website' => $attributes['company_website'] ?? $user->employerProfile?->company_website,
                    'company_details' => $attributes['company_details'] ?? $user->employerProfile?->company_details,
                ],
            );
        }

        $this->activities->record(
            $user,
            UserActivityType::UpdatedProfile,
            'Profile details were updated.',
            $user,
        );

        return $user->fresh(['candidateProfile', 'employerProfile']) ?? $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAccount(User $user, array $attributes): User
    {
        $user->fill([
            'username' => $attributes['username'] ?? $user->username,
            'mobile' => array_key_exists('mobile', $attributes) ? $attributes['mobile'] : $user->mobile,
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
