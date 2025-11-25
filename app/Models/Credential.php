<?php

namespace App\Models;

use App\Enums\Integrations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    protected $guarded = null;

    protected $casts = [
        'integration' => Integrations::class,
    ];

    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn () => json_decode(Crypt::decryptString($this->attributes['encrypted_data'] ?? ''), true),
            set: fn (mixed $value) => ['encrypted_data' => Crypt::encryptString(json_encode($value))],
        );
    }
}
