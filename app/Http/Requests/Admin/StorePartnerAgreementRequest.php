<?php

namespace App\Http\Requests\Admin;

use App\Models\ChannelPartner;
use Illuminate\Foundation\Http\FormRequest;

class StorePartnerAgreementRequest extends FormRequest
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
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:36'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
