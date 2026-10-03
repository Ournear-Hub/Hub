<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreRelationshipReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEmployer() === true
            || $this->user()?->isCandidate() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body') && is_string($this->input('body'))) {
            $this->merge([
                'body' => trim(strip_tags($this->input('body'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'A rating from 1 to 5 is required.',
            'rating.integer' => 'Rating must be a whole number from 1 to 5.',
            'rating.min' => 'Rating must be between 1 and 5.',
            'rating.max' => 'Rating must be between 1 and 5.',
            'body.required' => 'Written feedback is required.',
        ];
    }
}
