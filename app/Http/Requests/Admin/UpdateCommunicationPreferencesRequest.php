<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommunicationChannel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunicationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('update', $target) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];
        foreach (CommunicationChannel::cases() as $channel) {
            $rules['channels.'.$channel->value] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, bool>
     */
    public function channels(): array
    {
        $channels = [];
        foreach (CommunicationChannel::cases() as $channel) {
            $channels[$channel->value] = $this->boolean('channels.'.$channel->value);
        }

        return $channels;
    }
}
