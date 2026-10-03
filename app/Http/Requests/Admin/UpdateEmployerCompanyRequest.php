<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployerCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('employer');

        return $profile instanceof EmployerProfile && $this->user()?->can('update', $profile) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:40'],
            'industry' => ['nullable', 'string', 'max:255'],
            'about_company' => ['nullable', 'string', 'max:5000'],
            'company_size' => ['nullable', 'string', 'max:100'],
            'company_website' => ['nullable', 'url:http,https', 'max:2048'],
            'company_details' => ['nullable', 'string', 'max:5000'],
            'location_ids' => ['sometimes', 'array'],
            'location_ids.*' => ['integer', Rule::exists('locations', 'id')],
            'benefits' => ['sometimes', 'array', 'max:30'],
            'benefits.*' => ['string', 'max:255'],
            'socials' => ['sometimes', 'array'],
            'socials.linkedin' => ['nullable', 'url:http,https', 'max:2048'],
            'socials.facebook' => ['nullable', 'url:http,https', 'max:2048'],
            'socials.twitter' => ['nullable', 'url:http,https', 'max:2048'],
            'socials.instagram' => ['nullable', 'url:http,https', 'max:2048'],
            'socials.youtube' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function companyAttributes(): array
    {
        $attributes = [];

        foreach ([
            'company_name',
            'contact_person',
            'mobile_number',
            'industry',
            'about_company',
            'company_size',
            'company_website',
            'company_details',
        ] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = trim((string) $this->input($field, ''));
            $attributes[$field] = $value === '' ? null : $value;
        }

        if ($this->exists('location_ids')) {
            $attributes['location_ids'] = array_values(array_unique(array_map(
                'intval',
                $this->input('location_ids', []),
            )));
        }

        if ($this->exists('benefits')) {
            $attributes['benefits'] = array_values(array_filter(
                array_map(fn ($item) => trim((string) $item), $this->input('benefits', [])),
                fn (string $item) => $item !== '',
            ));
        }

        if ($this->exists('socials') && is_array($this->input('socials'))) {
            $attributes['socials'] = $this->input('socials');
        }

        return $attributes;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach ([
            'company_name',
            'contact_person',
            'mobile_number',
            'industry',
            'about_company',
            'company_size',
            'company_website',
            'company_details',
        ] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($this->exists('socials') && is_array($this->input('socials'))) {
            $socials = $this->input('socials');
            foreach ($socials as $key => $value) {
                $socials[$key] = is_string($value) && trim($value) === '' ? null : $value;
            }
            $merge['socials'] = $socials;
        }

        $this->merge($merge);
    }
}
