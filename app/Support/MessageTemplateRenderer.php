<?php

namespace App\Support;

use App\Enums\MessageTemplateChannel;

class MessageTemplateRenderer
{
    /**
     * @param  array<string, string>  $variables
     * @param  list<string>  $allowedVariables
     */
    public function render(
        string $template,
        array $variables,
        array $allowedVariables,
        MessageTemplateChannel $channel,
    ): string {
        $allowed = array_fill_keys($allowedVariables, true);

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/',
            function (array $matches) use ($variables, $allowed, $channel): string {
                $key = $matches[1];
                if (! isset($allowed[$key])) {
                    return '';
                }

                $value = (string) ($variables[$key] ?? '');

                if ($channel === MessageTemplateChannel::Email) {
                    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }

                return $value;
            },
            $template,
        );
    }

    /**
     * @return list<string>
     */
    public function extractPlaceholders(string $template): array
    {
        preg_match_all('/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/', $template, $matches);

        if ($matches[1] === []) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }
}
