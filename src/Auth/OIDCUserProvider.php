<?php

/** @noinspection ContractViolationInspection */

namespace Maicol07\OIDCClient\Auth;

use AssertionError;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Maicol07\OIDCClient\Models\User;
use Maicol07\OpenIDConnect\UserInfo;

class OIDCUserProvider implements UserProvider
{
    final public function retrieveByInfo(UserInfo $user_info): User
    {
        $attrs = $user_info->attrs();
        $uuid = $attrs->pull('sub');

        $user = config('auth.providers.users.model')::where('uuid', $uuid)->firstOrNew();
        try {
            assert($user instanceof User);
        } catch (AssertionError) {
            throw new AssertionError('User model must extend ' . User::class);
        }

        /** @noinspection UnusedFunctionResultInspection */
        $attrs->each(fn (mixed $value, string $attr): mixed => $user->$attr = $value);

        return $user;
    }

    #[\Override]
    final public function retrieveById(mixed $identifier): ?User
    {
        return null;
    }

    #[\Override]
    final public function retrieveByToken(mixed $identifier, mixed $token): ?User
    {
        return null;
    }

    #[\Override]
    final public function updateRememberToken(Authenticatable|User $user, mixed $token): void
    {
    }

    #[\Override]
    final public function retrieveByCredentials(array $credentials): ?User
    {
        return null;
    }

    #[\Override]
    final public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return true;
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[\SensitiveParameter] array $credentials,
        bool $force = false
    ) {
    }
}
