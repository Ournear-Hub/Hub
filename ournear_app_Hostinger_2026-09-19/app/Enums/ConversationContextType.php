<?php

namespace App\Enums;

enum ConversationContextType: string
{
    case Employer = 'employer';
    case Job = 'job';
    case Application = 'application';
    case Submission = 'submission';
    case Placement = 'placement';
    case Employee = 'employee';
    case Replacement = 'replacement';
    case Invoice = 'invoice';

    public function url(int $id): string
    {
        return match ($this) {
            self::Employer => '/admin/employers?employer='.$id,
            self::Job => '/admin/jobs?job='.$id,
            self::Application => '/admin/applications?application='.$id,
            self::Submission => '/admin/submissions?submission='.$id,
            self::Placement => '/admin/placements?placement='.$id,
            self::Employee => '/admin/employees?employee='.$id,
            self::Replacement => '/admin/replacements?replacement='.$id,
            self::Invoice => '/admin/finance?invoice='.$id,
        };
    }
}
