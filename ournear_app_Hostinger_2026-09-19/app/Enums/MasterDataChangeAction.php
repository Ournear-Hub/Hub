<?php

namespace App\Enums;

enum MasterDataChangeAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
}
