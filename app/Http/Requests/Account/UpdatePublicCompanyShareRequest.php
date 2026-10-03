<?php

namespace App\Http\Requests\Account;

use App\Models\EmployerProfile;
use App\Support\EmployerPublicShare;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicCompanyShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $profile = $user?->employerProfile;

        return $user?->canAccessHiringWorkspace() === true
            && $profile instanceof EmployerProfile
            && (int) $profile->user_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (EmployerPublicShare::keys() as $key) {
            $rules[$key] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, bool>
     */
    public function shareAttributes(): array
    {
        $share = [];

        foreach (EmployerPublicShare::keys() as $key) {
            if ($this->exists($key)) {
                $share[$key] = $this->boolean($key);
            }
        }

        return $share;
    }
}
