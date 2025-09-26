<?php

/** @noinspection ContractViolationInspection */

namespace Maicol07\OIDCClient\Auth;

use AssertionError;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Maicol07\OIDCClient\Models\OidcAuthMapping;
use Maicol07\OIDCClient\Models\Traits\LogsInWithOidc;
use Maicol07\OpenIDConnect\UserInfo;

class OIDCUserProvider implements UserProvider
{
    final public function retrieveByInfo(string $issuer, UserInfo $user_info): Authenticatable
    {
        /** @var class-string<Authenticatable> $user_class */
        $user_class = config('auth.providers.users.model');
        try {
            assert(in_array(LogsInWithOidc::class, class_uses($user_class), true));
        } catch (AssertionError) {
            throw new AssertionError('User model must use '.LogsInWithOidc::class);
        }

        session(['oidc_id_token' => $user_info->id_token]);

        $mapping = OidcAuthMapping::firstOrNew([
            'sub' => $user_info->sub,
            'issuer' => $issuer,
        ]);
        $user = $mapping->user()->firstOrCreate([
            'email' => $user_info->email,
            'email_verified_at' => $user_info->email_verified ? now() : null,
        ], config('oidc.user_creation_attributes', static fn (UserInfo $user_info): array => [
            'first_name' => $user_info->given_name,
            'last_name' => $user_info->family_name,
        ])($issuer, $user_info, $mapping));
        $mapping->user()->associate($user);
        $mapping->save();

        // Temporarily store the OIDC UserInfo in the user model
        $user->oidcUserInfo = $user_info;

        return $user;
    }

    #[\Override]
    final public function retrieveById(mixed $identifier): ?Authenticatable
    {
        return null;
    }

    #[\Override]
    final public function retrieveByToken(mixed $identifier, mixed $token): ?Authenticatable
    {
        return null;
    }

    #[\Override]
    final public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, mixed $token): void {}

    #[\Override]
    final public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return null;
    }

    #[\Override]
    final public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials): bool
    {
        return true;
    }

    public function rehashPasswordIfRequired(
        \Illuminate\Contracts\Auth\Authenticatable $user,
        #[\SensitiveParameter] array $credentials,
        bool $force = false
    ): void {}
}
