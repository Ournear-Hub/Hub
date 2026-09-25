<?php

namespace App\Enums;

enum DocumentType: string
{
    case Certificate = 'certificate';
    case Licence = 'licence';
    case Qualification = 'qualification';
    case Experience = 'experience';
    case Payslip = 'payslip';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Certificate => 'Certificate',
            self::Licence => 'Licence',
            self::Qualification => 'Qualification',
            self::Experience => 'Experience',
            self::Payslip => 'Payslip',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
