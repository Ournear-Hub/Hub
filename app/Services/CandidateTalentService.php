<?php

namespace App\Services;

use App\Enums\LanguageProficiency;
use App\Enums\SecurityActivityType;
use App\Enums\UserActivityType;
use App\Models\CandidateCertification;
use App\Models\CandidateDocument;
use App\Models\CandidateEducation;
use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;
use App\Models\CandidateProject;
use App\Models\SavedSearch;
use App\Models\User;
use App\Support\CandidatePublicShare;
use Illuminate\Validation\ValidationException;

class CandidateTalentService
{
    public function __construct(
        private UserActivityService $activities,
        private SecurityAuditService $audit,
        private CandidateProfessionalProfileService $professional,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addCertification(CandidateProfile $profile, array $attributes, ?User $actor = null): CandidateCertification
    {
        $certification = $profile->certifications()->create([
            'name' => $attributes['name'],
            'issuer' => $attributes['issuer'] ?? null,
            'issued_on' => $attributes['issued_on'] ?? null,
            'expires_on' => $attributes['expires_on'] ?? null,
            'candidate_document_id' => $this->ownedDocumentId($profile, $attributes['candidate_document_id'] ?? null),
        ]);

        $this->professional->refreshCompletion($profile);
        $this->recordProfileUpdate($profile, $actor, 'A certification was added.');

        return $certification->fresh() ?? $certification;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateCertification(CandidateCertification $certification, array $attributes, ?User $actor = null): CandidateCertification
    {
        $profile = $certification->candidateProfile;
        $certification->fill([
            'name' => $attributes['name'] ?? $certification->name,
            'issuer' => array_key_exists('issuer', $attributes) ? $attributes['issuer'] : $certification->issuer,
            'issued_on' => array_key_exists('issued_on', $attributes) ? $attributes['issued_on'] : $certification->issued_on,
            'expires_on' => array_key_exists('expires_on', $attributes) ? $attributes['expires_on'] : $certification->expires_on,
            'candidate_document_id' => array_key_exists('candidate_document_id', $attributes)
                ? $this->ownedDocumentId($profile, $attributes['candidate_document_id'])
                : $certification->candidate_document_id,
        ])->save();

        $this->recordProfileUpdate($profile, $actor, 'A certification was updated.');

        return $certification->fresh() ?? $certification;
    }

    public function deleteCertification(CandidateCertification $certification, ?User $actor = null): void
    {
        $profile = $certification->candidateProfile;
        $certification->delete();
        $this->recordProfileUpdate($profile, $actor, 'A certification was removed.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addLanguage(CandidateProfile $profile, array $attributes, ?User $actor = null): CandidateLanguage
    {
        $name = trim((string) $attributes['name']);
        $this->assertUniqueLanguage($profile, $name);

        $language = $profile->languages()->create([
            'name' => $name,
            'proficiency' => $attributes['proficiency'] ?? null,
        ]);

        $this->recordProfileUpdate($profile, $actor, 'A language was added.');

        return $language->fresh() ?? $language;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateLanguage(CandidateLanguage $language, array $attributes, ?User $actor = null): CandidateLanguage
    {
        $profile = $language->candidateProfile;
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $language->name;
        $this->assertUniqueLanguage($profile, $name, $language->id);

        $language->fill([
            'name' => $name,
            'proficiency' => array_key_exists('proficiency', $attributes) ? $attributes['proficiency'] : $language->proficiency,
        ])->save();

        $this->recordProfileUpdate($profile, $actor, 'A language was updated.');

        return $language->fresh() ?? $language;
    }

    public function deleteLanguage(CandidateLanguage $language, ?User $actor = null): void
    {
        $profile = $language->candidateProfile;
        $language->delete();
        $this->recordProfileUpdate($profile, $actor, 'A language was removed.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addEducation(CandidateProfile $profile, array $attributes, ?User $actor = null): CandidateEducation
    {
        $education = $profile->educations()->create($attributes);
        $this->recordProfileUpdate($profile, $actor, 'An education record was added.');

        return $education->fresh() ?? $education;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateEducation(CandidateEducation $education, array $attributes, ?User $actor = null): CandidateEducation
    {
        $education->fill($attributes)->save();
        $this->recordProfileUpdate($education->candidateProfile, $actor, 'An education record was updated.');

        return $education->fresh() ?? $education;
    }

    public function deleteEducation(CandidateEducation $education, ?User $actor = null): void
    {
        $profile = $education->candidateProfile;
        $education->delete();
        $this->recordProfileUpdate($profile, $actor, 'An education record was removed.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addProject(CandidateProfile $profile, array $attributes, ?User $actor = null): CandidateProject
    {
        $project = $profile->projects()->create($attributes);
        $this->recordProfileUpdate($profile, $actor, 'A project was added.');

        return $project->fresh() ?? $project;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProject(CandidateProject $project, array $attributes, ?User $actor = null): CandidateProject
    {
        $project->fill($attributes)->save();
        $this->recordProfileUpdate($project->candidateProfile, $actor, 'A project was updated.');

        return $project->fresh() ?? $project;
    }

    public function deleteProject(CandidateProject $project, ?User $actor = null): void
    {
        $profile = $project->candidateProfile;
        $project->delete();
        $this->recordProfileUpdate($profile, $actor, 'A project was removed.');
    }

    /**
     * @param  array<string, mixed>  $share
     * @return array<string, bool>
     */
    public function updatePublicShare(CandidateProfile $profile, array $share, ?User $actor = null): array
    {
        $profile->public_share = CandidatePublicShare::normalize($share);
        $profile->save();
        $this->recordProfileUpdate($profile, $actor, 'Public profile sharing was updated.');

        return $profile->publicShare();
    }

    public function recordInterest(CandidateProfile $profile, string $note, User $actor): void
    {
        $user = $profile->user ?? $profile->user()->first();
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'note' => 'This talent record has no platform user.',
            ]);
        }

        $this->activities->record(
            $user,
            UserActivityType::TalentInterest,
            $note,
            $actor,
            ['kind' => 'interest'],
        );

        $this->audit->record(
            SecurityActivityType::TalentInterestRecorded,
            'Talent interest recorded for '.$user->email.'.',
            $actor,
            $user,
            ['note' => $note],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function saveSearch(User $actor, string $name, array $filters): SavedSearch
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a name for this search.',
            ]);
        }

        if (SavedSearch::query()->where('user_id', $actor->id)->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'You already saved a search with this name.',
            ]);
        }

        return SavedSearch::query()->create([
            'user_id' => $actor->id,
            'name' => $name,
            'filters' => $filters,
        ]);
    }

    public function deleteSearch(User $actor, SavedSearch $search): void
    {
        abort_unless((int) $search->user_id === (int) $actor->id, 403);
        $search->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchesFor(User $actor): array
    {
        return SavedSearch::query()
            ->where('user_id', $actor->id)
            ->orderBy('name')
            ->get()
            ->map(fn (SavedSearch $search) => $search->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function proficiencyOptions(): array
    {
        return LanguageProficiency::options();
    }

    private function ownedDocumentId(CandidateProfile $profile, mixed $id): ?int
    {
        if (! filled($id)) {
            return null;
        }

        $documentId = (int) $id;
        $exists = CandidateDocument::query()
            ->whereKey($documentId)
            ->where('candidate_profile_id', $profile->id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'candidate_document_id' => 'Choose a document from this candidate library.',
            ]);
        }

        return $documentId;
    }

    private function assertUniqueLanguage(CandidateProfile $profile, string $name, ?int $ignoreId = null): void
    {
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a language name.',
            ]);
        }

        $query = $profile->languages()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'That language is already on this profile.',
            ]);
        }
    }

    private function recordProfileUpdate(CandidateProfile $profile, ?User $actor, string $body): void
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
