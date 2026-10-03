<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSocialLinksRequest extends FormRequest
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
            'links' => ['required', 'array', 'min:1'],
            'links.*.id' => ['required', 'integer', 'exists:social_links,id'],
            'links.*.url' => ['nullable', 'string', 'max:500'],
            'links.*.is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('links', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $enabled = filter_var($row['is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $url = trim((string) ($row['url'] ?? ''));

                if ($enabled && $url === '') {
                    $validator->errors()->add("links.{$index}.url", 'A URL is required to enable this platform.');
                    continue;
                }

                if ($url !== '' && ! $this->isSafeHttpUrl($url)) {
                    $validator->errors()->add("links.{$index}.url", 'Enter a valid http or https URL.');
                }
            }
        });
    }

    private function isSafeHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    protected function prepareForValidation(): void
    {
        $links = $this->input('links');
        if (! is_array($links)) {
            return;
        }

        foreach ($links as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('is_enabled', $row)) {
                $links[$index]['is_enabled'] = filter_var($row['is_enabled'], FILTER_VALIDATE_BOOLEAN);
            }

            if (array_key_exists('url', $row)) {
                $links[$index]['url'] = trim((string) $row['url']);
            }
        }

        $this->merge(['links' => $links]);
    }
}
