<?php

namespace App\Enums;

enum CrmLeadSource: string
{
    case Manual = 'manual';
    case EmployerEnquiry = 'employer_enquiry';
    case ContactEnquiry = 'contact_enquiry';
    case ChannelPartner = 'channel_partner';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::EmployerEnquiry => 'Employer enquiry',
            self::ContactEnquiry => 'Contact enquiry',
            self::ChannelPartner => 'Channel partner',
        };
    }
}
