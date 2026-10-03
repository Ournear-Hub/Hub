<?php

namespace App\Http\Requests\Account;

use App\Enums\PreferredLanguage;
use App\Http\Requests\Concerns\ValidatesAccountMobile;
use App\Services\UserPhotoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    use ValidatesAccountMobile;

    public function authorize(): bool
    {
        return $this->user()?->isAccountMember() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:32'],
            'mobile_verified_at' => ['prohibited'],
            'location' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'professional_headline' => ['nullable', 'string', 'max:160'],
            'preferred_language' => ['required', Rule::enum(PreferredLanguage::class)],
            'photo' => [
                'nullable',
                'file',
                'max:'.UserPhotoService::MAX_KILOBYTES,
                'mimes:'.implode(',', UserPhotoService::allowedExtensions()),
                'mimetypes:'.implode(',', UserPhotoService::allowedMimes()),
            ],
            'remove_photo' => ['sometimes', 'boolean'],
        ];

        if ($this->user()?->isCandidate()) {
            $rules['experience'] = ['nullable', 'string', 'max:255'];
            $rules['qualification'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->user()?->isEmployer()) {
            $rules['company_name'] = ['nullable', 'string', 'max:255'];
            $rules['contact_person'] = ['nullable', 'string', 'max:255'];
            $rules['mobile_number'] = ['nullable', 'string', 'max:40'];
            $rules['industry'] = ['nullable', 'string', 'max:255'];
            $rules['about_company'] = ['nullable', 'string', 'max:5000'];
            $rules['company_size'] = ['nullable', 'string', 'max:100'];
            $rules['company_website'] = ['nullable', 'url:http,https', 'max:2048'];
            $rules['company_details'] = ['nullable', 'string', 'max:5000'];
            $rules['location_ids'] = ['sometimes', 'array'];
            $rules['location_ids.*'] = ['integer', Rule::exists('locations', 'id')];
            $rules['benefits'] = ['sometimes', 'array', 'max:30'];
            $rules['benefits.*'] = ['string', 'max:255'];
            $rules['socials'] = ['sometimes', 'array'];
            $rules['socials.linkedin'] = ['nullable', 'url:http,https', 'max:2048'];
            $rules['socials.facebook'] = ['nullable', 'url:http,https', 'max:2048'];
            $rules['socials.twitter'] = ['nullable', 'url:http,https', 'max:2048'];
            $rules['socials.instagram'] = ['nullable', 'url:http,https', 'max:2048'];
            $rules['socials.youtube'] = ['nullable', 'url:http,https', 'max:2048'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $mobile = $this->string('mobile')->toString();
        $location = $this->string('location')->toString();
        $bio = $this->string('bio')->toString();
        $headline = $this->string('professional_headline')->toString();

        $attributes = [
            'name' => $this->string('name')->toString(),
            'username' => $this->string('username')->toString(),
            'mobile' => $mobile === '' ? null : $mobile,
            'location' => $location === '' ? null : $location,
            'bio' => $bio === '' ? null : $bio,
            'professional_headline' => $headline === '' ? null : $headline,
            'preferred_language' => $this->string('preferred_language')->toString(),
        ];

        if ($this->user()?->isCandidate()) {
            $experience = $this->string('experience')->toString();
            $qualification = $this->string('qualification')->toString();
            $attributes['experience'] = $experience === '' ? null : $experience;
            $attributes['qualification'] = $qualification === '' ? null : $qualification;
        }

        if ($this->user()?->isEmployer()) {
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
                $value = $this->string($field)->toString();
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
        }

        return $attributes;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedMobile();

        $merge = [];

        if ($this->has('remove_photo')) {
            $merge['remove_photo'] = $this->boolean('remove_photo');
        }

        if ($this->exists('socials') && is_array($this->input('socials'))) {
            $socials = $this->input('socials');
            foreach ($socials as $key => $value) {
                $socials[$key] = is_string($value) && trim($value) === '' ? null : $value;
            }
            $merge['socials'] = $socials;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateNormalizedMobile($validator, $this->user()?->mobile, $this->user()?->id);
    }
}
