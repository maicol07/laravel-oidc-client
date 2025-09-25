<?php

namespace Maicol07\OIDCClient\Events;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Maicol07\OpenIDConnect\UserInfo;

class LoginWithOIDC extends Login
{
    use Dispatchable;

    public function __construct(
        string $guard,
        Authenticatable $user,
        bool $remember,
        public readonly UserInfo $userInfo
    ) {
        parent::__construct($guard, $user, $remember);
    }
}
