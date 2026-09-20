<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function home(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Account/Home', [
            'role' => $user->role->value,
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['candidateProfile', 'employerProfile']);

        return Inertia::render('Account/Profile', [
            'role' => $user->role->value,
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                ...($user->isCandidate()
                    ? ($user->candidateProfile?->toAccountArray() ?? [
                        'mobile' => null,
                        'location' => null,
                        'experience' => null,
                        'qualification' => null,
                    ])
                    : ($user->employerProfile?->toAccountArray() ?? [
                        'company_name' => null,
                        'contact_person' => null,
                        'mobile_number' => null,
                        'industry' => null,
                    ])),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['name' => $request->string('name')->toString()]);

        if ($user->isCandidate()) {
            $user->candidateProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $request->safe()->only(['mobile', 'location', 'experience', 'qualification'])
            );
        } else {
            $user->employerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $request->safe()->only(['company_name', 'contact_person', 'mobile_number', 'industry'])
            );
        }

        return back()->with('success', 'Your profile has been updated.');
    }
}
