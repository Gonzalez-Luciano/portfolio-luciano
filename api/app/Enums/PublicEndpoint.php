<?php

namespace App\Enums;

enum PublicEndpoint: string
{
    case Profile = 'profile';
    case Site = 'site';
    case Experiences = 'experiences';
    case WorkCases = 'work-cases';
    case Projects = 'projects';
    case Technologies = 'technologies';
}
