<?php

namespace App\Http\Requests\Account;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $application = $this->route('application');

        return $user?->isCandidate() === true
            && $application instanceof Application
            && (int) $application->user_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
