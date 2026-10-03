<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\SendWhatsAppOtpRequest;
use App\Http\Requests\Account\VerifyWhatsAppOtpRequest;
use App\Models\User;
use App\Services\WhatsAppVerificationService;
use Illuminate\Http\RedirectResponse;

class WhatsAppVerificationController extends Controller
{
    public function send(SendWhatsAppOtpRequest $request, WhatsAppVerificationService $verification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAccountMember(), 403);
        $this->authorize('update', $user);

        $result = $verification->send($user, $request);

        return back()->with('status', 'A verification code was sent to your WhatsApp number.')->with(
            'whatsapp_otp_retry_after',
            $result['retry_after'],
        );
    }

    public function verify(VerifyWhatsAppOtpRequest $request, WhatsAppVerificationService $verification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAccountMember(), 403);
        $this->authorize('update', $user);

        $verification->verify($user, $request->string('otp')->toString(), $request);

        return back()->with('success', 'Your WhatsApp number has been verified.');
    }
}
