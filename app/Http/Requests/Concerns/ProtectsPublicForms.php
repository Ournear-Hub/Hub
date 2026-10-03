<?php

namespace App\Http\Requests\Concerns;

use App\Services\TurnstileService;
use Illuminate\Validation\Validator;

trait ProtectsPublicForms
{
    /**
     * @return array<string, mixed>
     */
    protected function publicProtectionRules(): array
    {
        return [
            'fax' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            app(TurnstileService::class)->validate($this, $validator);
        });
    }
}
