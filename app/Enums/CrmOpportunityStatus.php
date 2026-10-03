<?php

namespace App\Enums;

enum CrmOpportunityStatus: string
{
    case NewLead = 'new_lead';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case RequirementReceived = 'requirement_received';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Converted = 'converted';
    case ActiveClient = 'active_client';

    public function label(): string
    {
        return match ($this) {
            self::NewLead => 'New Lead',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::RequirementReceived => 'Requirement Received',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Converted => 'Converted',
            self::ActiveClient => 'Active Client',
        };
    }

    public function requiresEmployer(): bool
    {
        return in_array($this, [self::Converted, self::ActiveClient], true);
    }

    public function isOpen(): bool
    {
        return ! $this->requiresEmployer();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
