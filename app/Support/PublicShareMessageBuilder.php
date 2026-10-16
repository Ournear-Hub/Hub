<?php

namespace App\Support;

final class PublicShareMessageBuilder
{
    /**
     * @param  array{title: string, url: string, company?: string|null, location?: string|null, subtitle?: string|null}  $context
     */
    public function whatsappText(string $type, array $context): string
    {
        $title = trim($context['title']);
        $url = trim($context['url']);

        return match ($type) {
            'job' => $this->jobWhatsapp($title, $context['company'] ?? null, $context['location'] ?? null, $url),
            'company' => "Explore this company on OurNear Hire: {$title} {$url}",
            'profile' => $this->profileWhatsapp($title, $context['subtitle'] ?? null, $url),
            default => "{$title} {$url}",
        };
    }

    /**
     * @param  array{title: string, url: string, company?: string|null, location?: string|null, subtitle?: string|null}  $context
     * @return array{subject: string, body: string}
     */
    public function email(string $type, array $context): array
    {
        $title = trim($context['title']);
        $url = trim($context['url']);

        return match ($type) {
            'job' => [
                'subject' => 'Job opportunity on OurNear Hire',
                'body' => $this->jobEmailBody($title, $context['company'] ?? null, $context['location'] ?? null, $url),
            ],
            'company' => [
                'subject' => "{$title} on OurNear Hire",
                'body' => "I found this company profile on OurNear Hire.\n\n{$title}\n{$url}",
            ],
            'profile' => [
                'subject' => "{$title} on OurNear Hire",
                'body' => $this->profileEmailBody($title, $context['subtitle'] ?? null, $url),
            ],
            default => [
                'subject' => 'Shared from OurNear Hire',
                'body' => "{$title}\n{$url}",
            ],
        };
    }

    /**
     * @param  array{title: string, url: string, company?: string|null, location?: string|null, subtitle?: string|null}  $context
     */
    public function nativeText(string $type, array $context): string
    {
        $title = trim($context['title']);
        $url = trim($context['url']);

        return match ($type) {
            'job' => $this->jobWhatsapp($title, $context['company'] ?? null, $context['location'] ?? null, $url),
            'company' => "Explore {$title} on OurNear Hire",
            'profile' => $this->profileWhatsapp($title, $context['subtitle'] ?? null, $url),
            default => $title,
        };
    }

    private function jobWhatsapp(string $title, ?string $company, ?string $location, string $url): string
    {
        $parts = ["Check out this job: {$title}"];
        if (filled($company)) {
            $parts[0] .= ' — '.$company;
        }
        if (filled($location)) {
            $parts[0] .= ' ('.$location.')';
        }

        return $parts[0].' '.$url;
    }

    private function jobEmailBody(string $title, ?string $company, ?string $location, string $url): string
    {
        $lines = [
            'I found this job on OurNear Hire and thought you might be interested.',
            '',
            $title,
        ];
        if (filled($company)) {
            $lines[] = $company;
        }
        if (filled($location)) {
            $lines[] = $location;
        }
        $lines[] = $url;

        return implode("\n", $lines);
    }

    private function profileWhatsapp(string $name, ?string $subtitle, string $url): string
    {
        $line = "View this profile on OurNear Hire: {$name}";
        if (filled($subtitle)) {
            $line .= ' — '.$subtitle;
        }

        return $line.' '.$url;
    }

    private function profileEmailBody(string $name, ?string $subtitle, string $url): string
    {
        $lines = [
            'I wanted to share this profile with you on OurNear Hire.',
            '',
            $name,
        ];
        if (filled($subtitle)) {
            $lines[] = $subtitle;
        }
        $lines[] = $url;

        return implode("\n", $lines);
    }
}
