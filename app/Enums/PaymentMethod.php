<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'Bank transfer';
    case Upi = 'UPI';
    case Cheque = 'Cheque';
    case Cash = 'Cash';
    case Other = 'Other';
}
