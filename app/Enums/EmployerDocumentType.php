<?php

namespace App\Enums;

enum EmployerDocumentType: string
{
    case CompanyDocument = 'company_document';
    case Agreement = 'agreement';
    case Certificate = 'certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CompanyDocument => 'Company document',
            self::Agreement => 'Company agreement',
            self::Certificate => 'Company certificate',
            self::Other => 'Other company document',
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
