<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RequestAccountEmailChangeRequest;
use App\Models\User;
use App\Services\AccountEmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailChangeController extends Controller
{
    public function requestChange(
        RequestAccountEmailChangeRequest $request,
        AccountEmailChangeService $emailChange,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $emailChange->requestChange($user, $request->string('email')->toString());

        $fresh = $user->fresh();
        if ($fresh?->hasVerifiedEmail() && $emailChange->pendingEmail($fresh) !== null) {
            return back()->with(
                'status',
                'We sent a confirmation link to your new email address. Your current email stays active until you confirm.',
            );
        }

        return back()->with(
            'status',
            'Your email was updated. Check your inbox to verify the new address.',
        );
    }

    public function verifyPendingChange(Request $request, AccountEmailChangeService $emailChange): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && (int) $request->route('id') === (int) $user->id, 403);
        $this->authorize('update', $user);

        $emailChange->confirmPendingEmail($user, (string) $request->route('hash'));

        return redirect()
            ->route('account.settings.security')
            ->with('success', 'Your email address has been updated and verified.');
    }
}
