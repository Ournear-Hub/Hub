<?php

namespace App\Services;

use App\Enums\NoticePeriod;
use App\Enums\UserActivityType;
use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateProfile;
use App\Models\EmploymentType;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateProfessionalProfileService
{
    public function __construct(private UserActivityService $activities) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfessional(CandidateProfile $profile, array $attributes, ?User $actor = null): CandidateProfile
    {
        foreach ([
            'current_designation',
            'current_company',
            'industry_id',
            'job_category_id',
            'current_salary',
            'expected_salary',
            'notice_period',
            'employment_preference',
            'experience',
            'experience_range_id',
            'qualification',
            'qualification_id',
            'willing_to_relocate',
            'availability',
        ] as $field) {
            if (array_key_exists($field, $attributes)) {
                $profile->{$field} = $attributes[$field];
            }
        }

        $profile->save();

        if (array_key_exists('skills', $attributes) && is_array($attributes['skills'])) {
            $this->syncSkillNames($profile, $attributes['skills']);
        }

        if (array_key_exists('preferred_location_ids', $attributes)) {
            $this->syncPreferredLocations($profile, $attributes['preferred_location_ids'] ?? []);
        }

        if (array_key_exists('preferred_employment_type_ids', $attributes)) {
            $this->syncPreferredEmploymentTypes($profile, $attributes['preferred_employment_type_ids'] ?? []);
        }

        $this->refreshCompletion($profile);
        $this->recordUpdate($profile, $actor, 'Professional profile was updated.');

        return $profile->fresh([
            'skills',
            'employmentHistories.industry',
            'preferredLocations',
            'preferredEmploymentTypes',
            'certifications',
            'languages',
            'industry',
            'jobCategory',
            'user',
        ]) ?? $profile;
    }

    public function addSkill(CandidateProfile $profile, string $name, ?User $actor = null): Skill
    {
        $display = Skill::displayName($name);
        $normalized = Skill::normalizeName($display);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a skill name.',
            ]);
        }

        $alreadyAttached = $profile->skills()
            ->where('normalized_name', $normalized)
            ->exists();

        if ($alreadyAttached) {
            throw ValidationException::withMessages([
                'name' => 'That skill is already on this profile.',
            ]);
        }

        $skill = Skill::findOrCreateFromName($display);
        $profile->skills()->syncWithoutDetaching([$skill->id]);

        $this->refreshCompletion($profile);
        $this->recordUpdate($profile, $actor, 'A professional skill was added.');

        return $skill;
    }

    public function removeSkill(CandidateProfile $profile, Skill $skill, ?User $actor = null): void
    {
        if (! $profile->skills()->where('skills.id', $skill->id)->exists()) {
            abort(404);
        }

        $profile->skills()->detach($skill->id);
        $this->refreshCompletion($profile);
        $this->recordUpdate($profile, $actor, 'A professional skill was removed.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createHistory(CandidateProfile $profile, array $attributes, ?User $actor = null): CandidateEmploymentHistory
    {
        return DB::transaction(function () use ($profile, $attributes, $actor) {
            $isInternship = (bool) ($attributes['is_internship'] ?? false);
            $isCurrent = $isInternship ? false : (bool) ($attributes['is_current'] ?? false);

            if ($isCurrent) {
                $this->clearCurrentFlags($profile);
            }

            $history = $profile->employmentHistories()->create([
                'company' => $attributes['company'],
                'designation' => $attributes['designation'],
                'industry_id' => $attributes['industry_id'] ?? null,
                'started_on' => $attributes['started_on'],
                'ended_on' => $isCurrent ? null : ($attributes['ended_on'] ?? null),
                'is_current' => $isCurrent,
                'is_internship' => $isInternship,
                'description' => $attributes['description'] ?? null,
            ]);

            $this->refreshCompletion($profile);
            $this->recordUpdate($profile, $actor, 'Employment history was added.');

            return $history->fresh('industry') ?? $history;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateHistory(CandidateEmploymentHistory $history, array $attributes, ?User $actor = null): CandidateEmploymentHistory
    {
        return DB::transaction(function () use ($history, $attributes, $actor) {
            $profile = $history->candidateProfile;
            $isInternship = array_key_exists('is_internship', $attributes)
                ? (bool) $attributes['is_internship']
                : (bool) $history->is_internship;
            $isCurrent = $isInternship
                ? false
                : (array_key_exists('is_current', $attributes)
                    ? (bool) $attributes['is_current']
                    : (bool) $history->is_current);

            if ($isCurrent) {
                $this->clearCurrentFlags($profile, $history->id);
            }

            $history->fill([
                'company' => $attributes['company'] ?? $history->company,
                'designation' => $attributes['designation'] ?? $history->designation,
                'industry_id' => array_key_exists('industry_id', $attributes) ? $attributes['industry_id'] : $history->industry_id,
                'started_on' => $attributes['started_on'] ?? $history->started_on,
                'ended_on' => $isCurrent ? null : ($attributes['ended_on'] ?? $history->ended_on),
                'is_current' => $isCurrent,
                'is_internship' => $isInternship,
                'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $history->description,
            ])->save();

            $this->refreshCompletion($profile);
            $this->recordUpdate($profile, $actor, 'Employment history was updated.');

            return $history->fresh('industry') ?? $history;
        });
    }

    public function deleteHistory(CandidateEmploymentHistory $history, ?User $actor = null): void
    {
        $profile = $history->candidateProfile;
        $history->delete();
        $this->refreshCompletion($profile);
        $this->recordUpdate($profile, $actor, 'Employment history was removed.');
    }

    public function refreshCompletion(CandidateProfile $profile): void
    {
        $user = $profile->user ?? $profile->user()->first();
        if (! $user instanceof User) {
            return;
        }

        $user->unsetRelation('candidateProfile');
        $user->forceFill([
            'profile_completion' => $user->calculateProfileCompletion(),
        ])->saveQuietly();
    }

    /**
     * @param  list<mixed>  $names
     */
    private function syncSkillNames(CandidateProfile $profile, array $names): void
    {
        $ids = [];
        $seen = [];

        foreach ($names as $name) {
            if (! is_string($name) && ! is_numeric($name)) {
                continue;
            }

            $display = Skill::displayName((string) $name);
            $normalized = Skill::normalizeName($display);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $ids[] = Skill::findOrCreateFromName($display)->id;
        }

        $profile->skills()->sync($ids);
    }

    /**
     * @param  list<mixed>  $ids
     */
    private function syncPreferredLocations(CandidateProfile $profile, array $ids): void
    {
        $locationIds = Location::query()
            ->whereIn('id', $this->uniquePositiveIds($ids))
            ->pluck('id')
            ->all();

        $profile->preferredLocations()->sync($locationIds);
    }

    /**
     * @param  list<mixed>  $ids
     */
    private function syncPreferredEmploymentTypes(CandidateProfile $profile, array $ids): void
    {
        $typeIds = EmploymentType::query()
            ->whereIn('id', $this->uniquePositiveIds($ids))
            ->pluck('id')
            ->all();

        $profile->preferredEmploymentTypes()->sync($typeIds);
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<int>
     */
    private function uniquePositiveIds(array $ids): array
    {
        $unique = [];

        foreach ($ids as $id) {
            $value = (int) $id;
            if ($value > 0) {
                $unique[$value] = $value;
            }
        }

        return array_values($unique);
    }

    private function clearCurrentFlags(CandidateProfile $profile, ?int $exceptId = null): void
    {
        $query = $profile->employmentHistories()->where('is_current', true);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update([
            'is_current' => false,
        ]);
    }

    private function recordUpdate(CandidateProfile $profile, ?User $actor, string $body): void
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

    /**
     * @return list<array{value: string, label: string}>
     */
    public function noticePeriodOptions(): array
    {
        return NoticePeriod::options();
    }
}
