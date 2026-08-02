<?php

namespace Maicol07\OIDCClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OidcAuthMapping extends Model
{
    protected $fillable = [
        'sub',
        'issuer',
        'id_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
