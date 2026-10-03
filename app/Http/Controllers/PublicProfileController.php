<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Models\User;
use App\Services\UnifiedProfilePresenter;
use App\Support\PublicContent;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function candidate(\Illuminate\Http\Request $request, User $user, UnifiedProfilePresenter $profiles): Response
    {
        abort_unless($user->isCandidate() && $user->is_active, 404);

        $viewer = $request->user('account');
        $isOwner = $viewer instanceof User && (int) $viewer->id === (int) $user->id;

        return Inertia::render('Profiles/Show', [
            'content' => PublicContent::common(),
            'profile' => $profiles->publicCandidate($user),
            'viewer' => [
                'is_owner' => $isOwner,
            ],
        ]);
    }

    public function company(EmployerProfile $employer, UnifiedProfilePresenter $profiles): Response
    {
        return Inertia::render('Companies/Show', [
            'content' => PublicContent::common(),
            'company' => $profiles->publicCompany($employer),
        ]);
    }
}
