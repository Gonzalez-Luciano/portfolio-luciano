<?php

namespace App\Enums;

enum OwnedAssetKind: string
{
    case ProfilePhoto = 'profile-photo';
    case ProjectImage = 'project-image';
    case TechnologyIcon = 'technology-icon';
    case Cv = 'cv';
}
