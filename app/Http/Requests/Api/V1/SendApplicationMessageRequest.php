<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class SendApplicationMessageRequest extends FormRequest
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
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
