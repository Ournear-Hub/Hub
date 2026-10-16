<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebsiteContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebsite() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.section_key' => ['required', 'string', 'max:100'],
            'sections.*.heading' => ['nullable', 'string', 'max:255'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:255'],
            'sections.*.body' => ['nullable', 'string', 'max:50000'],
            'sections.*.primary_button_label' => ['nullable', 'string', 'max:100'],
            'sections.*.secondary_button_label' => ['nullable', 'string', 'max:100'],
            'sections.*.image_path' => ['nullable', 'string', 'max:500'],
            'sections.*.media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'sections.*.is_active' => ['sometimes', 'boolean'],
            'sections.*.extra' => ['nullable', 'array'],
            'sections.*.extra.phone' => ['nullable', 'string', 'max:255'],
            'sections.*.extra.whatsapp' => ['nullable', 'string', 'max:255'],
            'sections.*.extra.email' => ['nullable', 'string', 'max:255'],
            'sections.*.extra.address' => ['nullable', 'string', 'max:1000'],
            'sections.*.extra.hours' => ['nullable', 'string', 'max:255'],
            'sections.*.extra.help' => ['nullable', 'string', 'max:2000'],
            'sections.*.extra.mission' => ['nullable', 'string', 'max:2000'],
            'sections.*.extra.vision' => ['nullable', 'string', 'max:2000'],
            'sections.*.extra.values' => ['nullable', 'string', 'max:2000'],
            'sections.*.extra.founder_name' => ['nullable', 'string', 'max:190'],
            'sections.*.extra.founder_title' => ['nullable', 'string', 'max:190'],
            'sections.*.extra.founder_description' => ['nullable', 'string', 'max:5000'],
            'sections.*.extra.image_alt' => ['nullable', 'string', 'max:255'],
            'sections.*.extra.image_enabled' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'sections.*.extra.mobile_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'sections.*.extra.image_object_position' => ['nullable', 'string', 'max:64'],
            'sections.*.extra.mobile_image_object_position' => ['nullable', 'string', 'max:64'],
            'sections.*.items' => ['nullable', 'array'],
            'sections.*.items.*.id' => ['required', 'integer', Rule::exists('content_items', 'id')],
            'sections.*.items.*.title' => ['required', 'string', 'max:255'],
            'sections.*.items.*.description' => ['nullable', 'string', 'max:5000'],
            'sections.*.items.*.icon' => ['nullable', 'string', 'max:100'],
            'sections.*.items.*.image_path' => ['nullable', 'string', 'max:500'],
            'sections.*.items.*.is_active' => ['sometimes', 'boolean'],
            'sections.*.items.*.bullets' => ['nullable'],
            'sections.*.items.*.bullets.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sections = $this->input('sections');
        if (! is_array($sections)) {
            return;
        }

        foreach ($sections as $index => $section) {
            if (! is_array($section)) {
                continue;
            }

            if (array_key_exists('is_active', $section)) {
                $sections[$index]['is_active'] = filter_var($section['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            if (array_key_exists('media_id', $section) && ($section['media_id'] === '' || $section['media_id'] === null)) {
                $sections[$index]['media_id'] = null;
            }

            if (is_array($section['extra'] ?? null)) {
                $extra = $section['extra'];
                if (array_key_exists('image_enabled', $extra)) {
                    $sections[$index]['extra']['image_enabled'] = filter_var($extra['image_enabled'], FILTER_VALIDATE_BOOLEAN)
                        ? '1'
                        : '0';
                }
                if (array_key_exists('mobile_media_id', $extra) && ($extra['mobile_media_id'] === '' || $extra['mobile_media_id'] === null)) {
                    unset($sections[$index]['extra']['mobile_media_id']);
                }
            }

            foreach ($section['items'] ?? [] as $itemIndex => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (array_key_exists('is_active', $item)) {
                    $sections[$index]['items'][$itemIndex]['is_active'] = filter_var($item['is_active'], FILTER_VALIDATE_BOOLEAN);
                }

                if (is_string($item['bullets'] ?? null)) {
                    $sections[$index]['items'][$itemIndex]['bullets'] = preg_split('/\r\n|\r|\n/', $item['bullets']) ?: [];
                }
            }
        }

        $this->merge(['sections' => $sections]);
    }
}
