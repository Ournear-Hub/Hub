<?php

namespace App\Http\Requests\Account;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $this->user()?->can('withdraw', $application) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
