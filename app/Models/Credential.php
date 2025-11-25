<?php

namespace App\Models;

use App\Enums\Integrations;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    protected $casts = [
        'integration' => Integrations::class,
    ];
}
