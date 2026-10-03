<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateProfile;
use App\Support\CandidatePublicShare;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $profile = $user?->candidateProfile;

        return $user?->isCandidate() === true
            && $profile instanceof CandidateProfile
            && $user->can('update', $profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (CandidatePublicShare::keys() as $key) {
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

        foreach (CandidatePublicShare::keys() as $key) {
            if ($this->exists($key)) {
                $share[$key] = $this->boolean($key);
            }
        }

        return $share;
    }
}
