<?php

namespace App\Http\Requests\Account;

use App\Enums\CommunicationChannel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountCommunicationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCandidate() === true;
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
