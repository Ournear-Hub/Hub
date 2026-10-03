<?php

namespace App\Enums;

enum EmployerEnquiryStatus: string
{
    case New = 'New';
    case Contacted = 'Contacted';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
    case Closed = 'Closed';

    public function toEmployerLabel(): string
    {
        return match ($this) {
            self::New => 'Received',
            self::Contacted, self::InProgress => 'Being handled',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
        };
    }
}
