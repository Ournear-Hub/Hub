<?php

namespace App\Http\Requests\Account;

use App\Enums\PreferredLanguage;
use App\Http\Requests\Concerns\ValidatesAccountMobile;
use App\Http\Requests\Concerns\ValidatesAccountWhatsApp;
use App\Models\Industry;
use App\Services\UserPhotoService;
use App\Support\NormalizedWebsiteUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    use ValidatesAccountMobile;
    use ValidatesAccountWhatsApp;

    public function authorize(): bool
    {
        return $this->user()?->isAccountMember() === true;
    }

    public function isCompanyProfileUpdate(): bool
    {
        return $this->boolean('_company_profile') && $this->user()?->canAccessHiringWorkspace() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        $rules = [
            '_company_profile' => ['sometimes', 'boolean'],
            'photo' => [
                'nullable',
                'file',
                'max:'.UserPhotoService::MAX_KILOBYTES,
                'mimes:'.implode(',', UserPhotoService::allowedExtensions()),
                'mimetypes:'.implode(',', UserPhotoService::allowedMimes()),
            ],
            'remove_photo' => ['sometimes', 'boolean'],
        ];

        if ($this->isCompanyProfileUpdate()) {
            $rules['photo'] = ['prohibited'];
            $rules['remove_photo'] = ['prohibited'];
        } else {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['username'] = ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)];
            $rules['mobile'] = ['nullable', 'string', 'max:32'];
            $rules['mobile_verified_at'] = ['prohibited'];
            $rules['location'] = ['nullable', 'string', 'max:255'];
            $rules['bio'] = ['nullable', 'string', 'max:1000'];
            $rules['professional_headline'] = ['nullable', 'string', 'max:160'];
            $rules['preferred_language'] = ['required', Rule::enum(PreferredLanguage::class)];
        }

        if ($this->user()?->isCandidate()) {
            $rules['experience'] = ['nullable', 'string', 'max:255'];
            $rules['qualification'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->user()?->canAccessHiringWorkspace()) {
            $rules['company_name'] = ['nullable', 'string', 'max:255'];
            $rules['contact_person'] = ['nullable', 'string', 'max:255'];
            $rules['mobile_number'] = ['nullable', 'string', 'max:40'];
            $rules['whatsapp_number'] = ['nullable', 'string', 'max:32'];
            $rules['industry'] = ['nullable', 'string', 'max:255'];
            $rules['industry_id'] = ['nullable', 'integer', Rule::exists('industries', 'id')];
            $rules['about_company'] = ['nullable', 'string', 'max:5000'];
            $rules['company_size'] = ['nullable', 'string', 'max:100'];
            $rules['company_website'] = ['nullable', 'string', 'max:2048'];
            $rules['company_details'] = ['nullable', 'string', 'max:5000'];
            $rules['location_ids'] = ['sometimes', 'array'];
            $rules['location_ids.*'] = ['integer', Rule::exists('locations', 'id')];
            $rules['benefits'] = ['sometimes', 'array', 'max:30'];
            $rules['benefits.*'] = ['string', 'max:255'];
            $rules['socials'] = ['sometimes', 'array'];
            $rules['socials.linkedin'] = ['nullable', 'string', 'max:2048'];
            $rules['socials.facebook'] = ['nullable', 'string', 'max:2048'];
            $rules['socials.twitter'] = ['nullable', 'string', 'max:2048'];
            $rules['socials.instagram'] = ['nullable', 'string', 'max:2048'];
            $rules['socials.youtube'] = ['nullable', 'string', 'max:2048'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $attributes = [];

        if (! $this->isCompanyProfileUpdate()) {
            $attributes = [
                'name' => $this->string('name')->toString(),
                'username' => $this->string('username')->toString(),
                'preferred_language' => $this->string('preferred_language')->toString(),
            ];

            foreach (['mobile', 'location', 'bio', 'professional_headline'] as $field) {
                if (! $this->exists($field)) {
                    continue;
                }

                $value = trim($this->string($field)->toString());
                $attributes[$field] = $value === '' ? null : $value;
            }
        }

        if ($this->user()?->isCandidate()) {
            foreach (['experience', 'qualification'] as $field) {
                if (! $this->exists($field)) {
                    continue;
                }

                $value = trim($this->string($field)->toString());
                $attributes[$field] = $value === '' ? null : $value;
            }
        }

        if ($this->isCompanyProfileUpdate() && $this->exists('whatsapp_number')) {
            $value = trim($this->string('whatsapp_number')->toString());
            $attributes['whatsapp_number'] = $value === '' ? null : $value;
        }

        if ($this->user()?->canAccessHiringWorkspace() && ($this->isCompanyProfileUpdate() || $this->hasAnyEmployerField())) {
            foreach ([
                'company_name',
                'contact_person',
                'mobile_number',
                'industry',
                'industry_id',
                'about_company',
                'company_size',
                'company_website',
                'company_details',
            ] as $field) {
                if (! $this->exists($field)) {
                    continue;
                }

                $value = trim($this->string($field)->toString());
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

            if ($this->exists('industry_id')) {
                $raw = $this->input('industry_id');
                $attributes['industry_id'] = is_numeric($raw) && (int) $raw > 0 ? (int) $raw : null;
            }

            if ($this->exists('socials') && is_array($this->input('socials'))) {
                $attributes['socials'] = $this->input('socials');
            }
        }

        return $attributes;
    }

    private function hasAnyEmployerField(): bool
    {
        foreach ([
            'company_name',
            'contact_person',
            'mobile_number',
            'industry',
            'industry_id',
            'about_company',
            'company_size',
            'company_website',
            'company_details',
            'location_ids',
            'benefits',
            'socials',
        ] as $field) {
            if ($this->exists($field)) {
                return true;
            }
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedMobile();
        $this->mergeNormalizedWhatsApp();

        $merge = [];

        if ($this->has('remove_photo')) {
            $merge['remove_photo'] = $this->boolean('remove_photo');
        }

        if ($this->has('_company_profile')) {
            $merge['_company_profile'] = $this->boolean('_company_profile');
        }

        if ($this->exists('socials') && is_array($this->input('socials'))) {
            $socials = $this->input('socials');
            foreach ($socials as $key => $value) {
                $socials[$key] = is_string($value) && trim($value) === '' ? null : $value;
            }
            $merge['socials'] = $socials;
        }

        if ($this->exists('company_website')) {
            $rawWebsite = trim((string) $this->input('company_website'));
            if ($rawWebsite === '') {
                $merge['company_website'] = null;
            } else {
                $normalized = NormalizedWebsiteUrl::normalize($rawWebsite);
                if ($normalized !== null) {
                    $merge['company_website'] = $normalized;
                }
            }
        }

        if ($this->exists('socials') && is_array($this->input('socials'))) {
            $socials = $merge['socials'] ?? $this->input('socials');
            foreach ($socials as $key => $value) {
                if (! is_string($value) || trim($value) === '') {
                    $socials[$key] = null;

                    continue;
                }
                $normalized = NormalizedWebsiteUrl::normalize($value);
                if ($normalized !== null) {
                    $socials[$key] = $normalized;
                }
            }
            $merge['socials'] = $socials;
        }

        if ($this->exists('industry_id')) {
            $rawIndustry = $this->input('industry_id');
            if ($rawIndustry === '' || $rawIndustry === null) {
                $merge['industry_id'] = null;
            } elseif (is_numeric($rawIndustry)) {
                $merge['industry_id'] = (int) $rawIndustry;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateNormalizedMobile($validator, $this->user()?->mobile, $this->user()?->id);

        $validator->after(function (Validator $validator): void {
            if ($this->exists('company_website')) {
                $rawWebsite = trim((string) $this->input('company_website'));
                if ($rawWebsite !== '' && NormalizedWebsiteUrl::normalize($rawWebsite) === null) {
                    $validator->errors()->add('company_website', 'Enter a valid website URL.');
                }
            }

            foreach (['linkedin', 'facebook', 'twitter', 'instagram', 'youtube'] as $key) {
                $raw = $this->input("socials.{$key}");
                if (is_string($raw) && trim($raw) !== '' && NormalizedWebsiteUrl::normalize(trim($raw)) === null) {
                    $validator->errors()->add("socials.{$key}", 'Enter a valid URL.');
                }
            }

            if ($this->user()?->canAccessHiringWorkspace() && $this->exists('industry_id')) {
                $industryId = $this->input('industry_id');
                if ($industryId !== null && $industryId !== '') {
                    $industry = Industry::query()->find((int) $industryId);
                    if ($industry && ! $industry->isSelectable()) {
                        $validator->errors()->add('industry_id', 'Select an active industry.');
                    }
                }
            }
        });
    }
}
