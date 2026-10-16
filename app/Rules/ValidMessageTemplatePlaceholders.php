<?php

namespace App\Rules;

use App\Support\MessageTemplateRenderer;
use App\Support\MessageTemplateVariableCatalog;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMessageTemplatePlaceholders implements ValidationRule
{
    public function __construct(private ?string $templateKey = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The template content must be a string.');

            return;
        }

        $renderer = app(MessageTemplateRenderer::class);
        $placeholders = $renderer->extractPlaceholders($value);
        if ($placeholders === []) {
            return;
        }

        $allowed = $this->templateKey !== null
            ? MessageTemplateVariableCatalog::allowedForKey($this->templateKey)
            : MessageTemplateVariableCatalog::globalVariables();

        $allowedMap = array_fill_keys($allowed, true);
        foreach ($placeholders as $placeholder) {
            if (! isset($allowedMap[$placeholder])) {
                $fail("Unsupported variable {{".$placeholder.'}}.');
            }
        }
    }
}
