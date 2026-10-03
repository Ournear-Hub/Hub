<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'Draft';
    case Issued = 'Issued';
    case PartiallyPaid = 'Partially Paid';
    case Paid = 'Paid';
    case Cancelled = 'Cancelled';

    public function isOpen(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyPaid], true);
    }

    public function countsTowardBilling(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyPaid, self::Paid], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled], true);
    }
}
