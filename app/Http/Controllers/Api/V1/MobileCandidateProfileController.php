<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesCandidateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreCandidateCertificationRequest;
use App\Http\Requests\Account\StoreCandidateEducationRequest;
use App\Http\Requests\Account\StoreCandidateSkillRequest;
use App\Http\Requests\Account\StoreEmploymentHistoryRequest;
use App\Http\Requests\Account\UpdateCandidateCertificationRequest;
use App\Http\Requests\Account\UpdateCandidateEducationRequest;
use App\Http\Requests\Account\UpdateEmploymentHistoryRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Account\UpdateProfessionalProfileRequest;
use App\Models\CandidateCertification;
use App\Models\CandidateEducation;
use App\Models\CandidateEmploymentHistory;
use App\Models\Skill;
use App\Services\AccountProfileService;
use App\Services\Api\V1\MobileCandidatePresenter;
use App\Services\CandidateProfessionalProfileService;
use App\Services\CandidateTalentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileCandidateProfileController extends Controller
{
    use ResolvesCandidateProfile;

    public function showPersonal(Request $request, MobileCandidatePresenter $presenter): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return response()->json([
            'data' => $presenter->personal($user),
        ]);
    }

    public function updatePersonal(
        UpdateProfileRequest $request,
        AccountProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $user = $request->user();
        $this->authorize('update', $user);

        $profiles->updateProfile(
            $user,
            $request->profileAttributes(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
            false,
        );

        return response()->json([
            'message' => 'Profile updated.',
            'data' => $presenter->personal($user->fresh()),
        ]);
    }

    public function showProfessional(Request $request, MobileCandidatePresenter $presenter): JsonResponse
    {
        $profile = $this->ownedProfile($request);
        $this->authorize('view', $profile);
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'data' => $presenter->professional($profile),
        ]);
    }

    public function updateProfessional(
        UpdateProfessionalProfileRequest $request,
        CandidateProfessionalProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->updateProfessional(
            $profile,
            $request->professionalAttributes(),
            $request->user(),
        );

        $profile = $profile->fresh();
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Professional profile updated.',
            'data' => $presenter->professional($profile),
        ]);
    }

    public function storeSkill(
        StoreCandidateSkillRequest $request,
        CandidateProfessionalProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->addSkill($profile, $request->skillName(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Skill added.',
            'data' => $presenter->professional($profile->fresh()),
        ], 201);
    }

    public function destroySkill(
        Request $request,
        Skill $skill,
        CandidateProfessionalProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->removeSkill($profile, $skill, $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Skill removed.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }

    public function storeHistory(
        StoreEmploymentHistoryRequest $request,
        CandidateProfessionalProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $profiles->createHistory($profile, $request->historyAttributes(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Employment history added.',
            'data' => $presenter->professional($profile->fresh()),
        ], 201);
    }

    public function updateHistory(
        UpdateEmploymentHistoryRequest $request,
        CandidateEmploymentHistory $history,
        CandidateProfessionalProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        $this->authorize('update', $history);
        abort_unless((int) $history->candidate_profile_id === (int) $profile->id, 403);

        $profiles->updateHistory($history, $request->historyAttributes(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Employment history updated.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }

    public function destroyHistory(
        Request $request,
        CandidateEmploymentHistory $history,
        CandidateProfessionalProfileService $profiles,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        $this->authorize('delete', $history);
        abort_unless((int) $history->candidate_profile_id === (int) $profile->id, 403);

        $profiles->deleteHistory($history, $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Employment history removed.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }

    public function storeEducation(
        StoreCandidateEducationRequest $request,
        CandidateTalentService $talent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $talent->addEducation($profile, $request->educationAttributes(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Education added.',
            'data' => $presenter->professional($profile->fresh()),
        ], 201);
    }

    public function updateEducation(
        UpdateCandidateEducationRequest $request,
        CandidateEducation $education,
        CandidateTalentService $talent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $education->candidate_profile_id === (int) $profile->id, 403);

        $talent->updateEducation($education, $request->educationAttributes(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Education updated.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }

    public function destroyEducation(
        Request $request,
        CandidateEducation $education,
        CandidateTalentService $talent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $education->candidate_profile_id === (int) $profile->id, 403);

        $talent->deleteEducation($education, $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Education removed.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }

    public function storeCertification(
        StoreCandidateCertificationRequest $request,
        CandidateTalentService $talent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);

        $talent->addCertification($profile, $request->certificationAttributes(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Certification added.',
            'data' => $presenter->professional($profile->fresh()),
        ], 201);
    }

    public function updateCertification(
        UpdateCandidateCertificationRequest $request,
        CandidateCertification $certification,
        CandidateTalentService $talent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $certification->candidate_profile_id === (int) $profile->id, 403);

        $talent->updateCertification($certification, $request->certificationAttributes(), $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Certification updated.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }

    public function destroyCertification(
        Request $request,
        CandidateCertification $certification,
        CandidateTalentService $talent,
        MobileCandidatePresenter $presenter,
    ): JsonResponse {
        $profile = $this->ownedProfile($request);
        $this->authorize('update', $profile);
        abort_unless((int) $certification->candidate_profile_id === (int) $profile->id, 403);

        $talent->deleteCertification($certification, $request->user());
        $this->loadProfessionalRelations($profile);

        return response()->json([
            'message' => 'Certification removed.',
            'data' => $presenter->professional($profile->fresh()),
        ]);
    }
}
