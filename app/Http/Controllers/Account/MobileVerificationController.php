<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SendMobileOtpRequest;
use App\Http\Requests\Account\VerifyMobileOtpRequest;
use App\Models\User;
use App\Services\MobileVerificationService;
use Illuminate\Http\RedirectResponse;

class MobileVerificationController extends Controller
{
    public function send(SendMobileOtpRequest $request, MobileVerificationService $verification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAccountMember(), 403);
        $this->authorize('update', $user);

        $result = $verification->send($user, $request);

        return back()->with('status', 'A verification code was sent to your mobile number.')->with(
            'mobile_otp_retry_after',
            $result['retry_after'],
        );
    }

    public function verify(VerifyMobileOtpRequest $request, MobileVerificationService $verification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAccountMember(), 403);
        $this->authorize('update', $user);

        $verification->verify($user, $request->string('otp')->toString(), $request);

        return back()->with('success', 'Your mobile number has been verified.');
    }
}
