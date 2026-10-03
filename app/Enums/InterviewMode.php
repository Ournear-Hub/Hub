<?php

namespace App\Enums;

enum InterviewMode: string
{
    case Phone = 'phone';
    case Video = 'video';
    case InPerson = 'in_person';
}
