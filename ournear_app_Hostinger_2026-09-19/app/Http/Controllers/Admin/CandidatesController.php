<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCandidateProfileRequest;
use App\Models\CandidateProfile;
use App\Models\ExperienceRange;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\RecruitmentActivity;
use App\Services\MasterData\MasterDataService;
use App\Support\CandidateQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidatesController extends Controller
{
    public function index(Request $request, CandidateQuery $candidates, MasterDataService $masterData): Response
    {
        $this->authorize('viewAny', CandidateProfile::class);

        $page = $candidates->adminIndex($request)
            ->through(fn (CandidateProfile $profile) => $profile->toAdminArray());

        $selected = $this->selectedProfile($request);

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $page,
            'selected' => $selected?->toAdminArray(),
            'applications' => $selected ? $this->applications($selected) : [],
            'activities' => $selected ? $this->activities($selected) : [],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'location_id' => $request->string('location_id')->toString(),
                'experience_range_id' => $request->string('experience_range_id')->toString(),
                'qualification_id' => $request->string('qualification_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'candidate' => $selected ? (string) $selected->id : '',
            ],
            'lookups' => $masterData->formOptions(),
        ]);
    }

    public function update(UpdateCandidateProfileRequest $request, CandidateProfile $candidate): RedirectResponse
    {
        $data = $request->validated();

        $candidate->user?->update(['name' => $data['name']]);

        $this->syncLookup($candidate, 'location_id', 'location', Location::class, $data['location_id'] ?? null);
        $this->syncLookup($candidate, 'experience_range_id', 'experience', ExperienceRange::class, $data['experience_range_id'] ?? null);
        $this->syncLookup($candidate, 'qualification_id', 'qualification', Qualification::class, $data['qualification_id'] ?? null);
        $candidate->mobile = $data['mobile'] ?? null;
        $candidate->save();

        return back()->with('success', 'Candidate profile updated.');
    }

    /**
     * @param  class-string  $model
     */
    private function syncLookup(CandidateProfile $profile, string $idKey, string $stringKey, string $model, mixed $id): void
    {
        if (filled($id)) {
            $record = $model::query()->find($id);
            $profile->{$idKey} = $record?->id;
            if ($record) {
                $profile->{$stringKey} = $record->name;
            }

            return;
        }

        $profile->{$idKey} = null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applications(CandidateProfile $profile): array
    {
        return $profile->applications()
            ->with(['job', 'screening'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($application) => $application->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activities(CandidateProfile $profile): array
    {
        $applicationIds = $profile->applications()->pluck('id');

        if ($applicationIds->isEmpty()) {
            return [];
        }

        return RecruitmentActivity::query()
            ->with('actor:id,name')
            ->whereIn('application_id', $applicationIds)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (RecruitmentActivity $activity) => [
                'id' => $activity->id,
                'title' => $activity->body ?: ($activity->from_status && $activity->to_status
                    ? $activity->from_status.' → '.$activity->to_status
                    : $activity->type->value),
                'meta' => trim(implode(' · ', array_filter([
                    $activity->actor?->name,
                    $activity->created_at?->toDateTimeString(),
                ]))),
            ])
            ->values()
            ->all();
    }

    private function selectedProfile(Request $request): ?CandidateProfile
    {
        $id = $request->integer('candidate');
        if ($id <= 0) {
            return null;
        }

        $profile = CandidateProfile::query()->withCount('applications')->find($id);

        if ($profile && $request->user()?->can('view', $profile)) {
            return $profile;
        }

        return null;
    }
}
