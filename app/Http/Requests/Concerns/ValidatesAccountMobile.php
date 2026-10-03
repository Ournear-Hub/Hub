<?php

namespace App\Http\Requests\Concerns;

use App\Support\MobileNumber;
use Illuminate\Validation\Validator;

trait ValidatesAccountMobile
{
    protected function mergeNormalizedMobile(): void
    {
        if (! $this->exists('mobile')) {
            return;
        }

        $mobile = $this->input('mobile');
        if (! is_string($mobile) || trim($mobile) === '') {
            $this->merge(['mobile' => null]);

            return;
        }

        $this->merge(['mobile' => trim($mobile)]);
    }

    protected function validateNormalizedMobile(Validator $validator, ?string $currentMobile, ?int $ignoreUserId): void
    {
        $validator->after(function (Validator $validator) use ($currentMobile, $ignoreUserId): void {
            $submitted = $this->input('mobile');
            if (! is_string($submitted) || $submitted === '') {
                return;
            }

            if (is_string($currentMobile) && trim($submitted) === trim($currentMobile)) {
                $normalizedCurrent = MobileNumber::normalize($currentMobile);
                if (is_string($normalizedCurrent)) {
                    $this->merge(['mobile' => $normalizedCurrent]);
                }

                return;
            }

            $normalized = MobileNumber::normalize($submitted);
            if (! is_string($normalized)) {
                $validator->errors()->add(
                    'mobile',
                    'Enter a valid mobile number with country code. India numbers can use +91, 91, or a 10-digit mobile number.',
                );

                return;
            }

            $this->merge(['mobile' => $normalized]);

            if (MobileNumber::same($normalized, $currentMobile)) {
                return;
            }

            if (MobileNumber::takenByAnotherUser($normalized, $ignoreUserId)) {
                $validator->errors()->add('mobile', 'That mobile number is already in use.');
            }
        });
    }
}
