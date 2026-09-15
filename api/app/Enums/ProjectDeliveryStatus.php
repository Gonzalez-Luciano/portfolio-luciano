<?php

namespace App\Enums;

enum ProjectDeliveryStatus: string
{
    case InProduction = 'in_production';
    case InUse = 'in_use';
    case PublicDemo = 'public_demo';
    case InDevelopment = 'in_development';
}
