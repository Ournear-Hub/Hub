<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class AccountPassword
{
    public static function rules(): Password
    {
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
