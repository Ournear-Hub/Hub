<?php

namespace App\Http\Requests\Admin;

use App\Models\ReferralLink;
use Illuminate\Foundation\Http\FormRequest;

class StoreReferralLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReferralLink::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'job_id' => ['nullable', 'integer', 'exists:job_listings,id'],
            'referrer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'code' => ['nullable', 'string', 'max:16'],
        ];
    }
}
