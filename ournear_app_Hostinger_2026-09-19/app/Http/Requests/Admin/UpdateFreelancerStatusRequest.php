<?php

namespace App\Http\Requests\Admin;

use App\Enums\FreelancerStatus;
use App\Models\Freelancer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFreelancerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $freelancer = $this->route('freelancer');

        return $freelancer instanceof Freelancer
            && $this->user()?->can('update', $freelancer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(FreelancerStatus::class)],
        ];
    }
}
