<?php

namespace App\Http\Requests\Admin;

use App\Models\ApplicationInterview;
use Illuminate\Foundation\Http\FormRequest;

class CancelApplicationInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $interview = $this->route('interview');

        return $interview instanceof ApplicationInterview
            && $this->user()?->can('update', $interview) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancelled_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
