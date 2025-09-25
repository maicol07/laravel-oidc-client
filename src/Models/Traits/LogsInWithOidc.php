<?php

namespace Maicol07\OIDCClient\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Maicol07\OIDCClient\Models\OidcAuthMapping;

trait LogsInWithOidc
{
    public function oidcAuthMappings(): HasMany
    {
        return $this->hasMany(OidcAuthMapping::class);
    }
}
