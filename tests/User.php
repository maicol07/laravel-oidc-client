<?php

namespace Maicol07\OIDCClient\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Maicol07\OIDCClient\Models\OidcAuthMapping;
use Maicol07\OIDCClient\Models\Traits\LogsInWithOidc;
use Maicol07\OpenIDConnect\UserInfo;

class User extends Authenticatable
{
    use LogsInWithOidc;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = true;

    public function mapOIDCUserinfo(string $issuer, UserInfo $user_info, OidcAuthMapping $mapping): void
    {
        $this->fill([
            'first_name' => $user_info->given_name,
            'last_name' => $user_info->family_name,
        ]);
    }
}
