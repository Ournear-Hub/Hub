<?php

namespace App\Enums;

enum ContactEnquiryStatus: string
{
    case New = 'New';
    case Contacted = 'Contacted';
    case Completed = 'Completed';
    case Closed = 'Closed';
}
