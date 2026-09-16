<?php

namespace App\Enums;

/** Declaration order is the public order of `profile.work_modes`. */
enum WorkMode: string
{
    case OnSite = 'on_site';
    case Hybrid = 'hybrid';
    case Remote = 'remote';
}
