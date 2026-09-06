<?php

namespace App\Enums;

enum TechnologyCategory: string
{
    case Backend = 'backend';
    case Data = 'data';
    case Integration = 'integration';
    case Collaboration = 'collaboration';
}
