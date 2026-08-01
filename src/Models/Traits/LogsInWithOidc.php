<?php

namespace Maicol07\OIDCClient\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Maicol07\OIDCClient\Models\OidcAuthMapping;
use Maicol07\OpenIDConnect\UserInfo;

trait LogsInWithOidc
{
    public function oidcAuthMappings(): HasMany
    {
        return $this->hasMany(OidcAuthMapping::class);
    }

    /**
     * Map OIDC UserInfo attributes to User model attributes.
     *
     * This method can be overridden in the User model to customize the mapping.
     *
     * @param  string  $issuer  The OIDC issuer.
     * @param  UserInfo  $user_info  The OIDC UserInfo object.
     * @param  OidcAuthMapping  $mapping  The OIDC Auth Mapping instance.
     */
    public function mapOIDCUserinfo(string $issuer, UserInfo $user_info, OidcAuthMapping $mapping): void
    {
        $this->fill([config(
            'oidc.user_creation_attributes', // TODO: Remove in next major release
            static fn (string $issuer, UserInfo $user_info): array => [
                'first_name' => $user_info->given_name,
                'last_name' => $user_info->family_name,
            ]
        )($issuer, $user_info, $mapping)]);
    }
}
