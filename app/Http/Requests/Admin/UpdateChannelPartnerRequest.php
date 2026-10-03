<?php

namespace App\Http\Requests\Admin;

use App\Models\ChannelPartner;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $partner = $this->route('partner');

        return $partner instanceof ChannelPartner
            && $this->user()?->can('update', $partner) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
