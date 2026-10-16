<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Account\RegisterRequest;

class MobileRegisterRequest extends RegisterRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['accept_privacy'] = ['accepted'];
        $rules['device_name'] = ['nullable', 'string', 'max:255'];

        return $rules;
    }
}
