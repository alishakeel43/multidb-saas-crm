<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Activity extends SpatieActivity
{
    use UsesLandlordConnection;
}
