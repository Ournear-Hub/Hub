<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case Website = 'website';
    case Mobile = 'mobile';
    case Google = 'google';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Whatsapp = 'whatsapp';
    case Referral = 'referral';
    case Freelancer = 'freelancer';
    case Employer = 'employer';
    case Campaign = 'campaign';
    case Admin = 'admin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Mobile => 'Mobile app',
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Whatsapp => 'WhatsApp',
            self::Referral => 'Referral',
            self::Freelancer => 'Freelancer',
            self::Employer => 'Employer',
            self::Campaign => 'Campaign',
            self::Admin => 'Admin',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $source) => [
            'value' => $source->value,
            'label' => $source->label(),
        ], self::cases());
    }
}
