<?php

namespace App\Enums;

enum JobPublishingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
