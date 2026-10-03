<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Notifications\DatabaseNotification;

class MarkNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notification = $this->route('notification');

        return $notification instanceof DatabaseNotification
            && $this->user()?->can('update', $notification) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
