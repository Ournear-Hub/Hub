<?php

namespace App\Enums;

enum ScreeningAction: string
{
    case Start = 'start';
    case Match = 'match';
    case Contact = 'contact';
    case Called = 'called';
    case Interviewed = 'interviewed';
    case Hold = 'hold';

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Start screening',
            self::Match => 'Mark matched',
            self::Contact => 'Contact',
            self::Called => 'Mark called',
            self::Interviewed => 'Mark interviewed',
            self::Hold => 'Hold',
        };
    }

    public function successMessage(): string
    {
        return match ($this) {
            self::Start => 'Screening started.',
            self::Match => 'Candidate marked matched. This does not determine suitability on its own.',
            self::Contact => 'Candidate marked as contacted.',
            self::Called => 'Candidate marked as called.',
            self::Interviewed => 'Candidate marked as interviewed.',
            self::Hold => 'Candidate put on hold.',
        };
    }
}
