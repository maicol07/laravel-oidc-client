<?php

/** @noinspection ContractViolationInspection */

namespace Maicol07\OIDCClient\Auth;

use AssertionError;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Maicol07\OIDCClient\Models\OidcAuthMapping;
use Maicol07\OIDCClient\Models\Traits\LogsInWithOidc;
use Maicol07\OpenIDConnect\UserInfo;

class OIDCUserProvider extends EloquentUserProvider
{
    public function __construct()
    {
        parent::__construct(
            app()->make(HasherContract::class),
            config('auth.providers.users.model')
        );
    }

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

        // @phpstan-ignore-next-line
        $mapping = OidcAuthMapping::firstOrNew([
            'sub' => $user_info->sub,
            'issuer' => $issuer,
        ]);

        // SECURITY (CWE-287 / CWE-290): the stable identity of an OIDC user is the
        // (issuer, sub) pair — never the email. The email claim is mutable and,
        // unless email_verified is true, may be an arbitrary value the End-User
        // typed at the IdP. Resolving the account by email (as this method used to
        // do) let anyone who could make a trusted IdP assert a victim's email log
        // in as that victim. Resolve through the mapping first, and only fall back
        // to an existing local account by *verified* email when the operator has
        // explicitly opted in via oidc.link_by_verified_email.
        $user = $mapping->exists ? $mapping->user : null;

        if ($user === null) {
            if ($user_info->email !== null
                && $user_info->email !== ''
                && (bool) $user_info->email_verified
                && config('oidc.link_by_verified_email', false)) {
                $user = $user_class::where('email', $user_info->email)->first();
            }

            $user ??= new $user_class(['email' => $user_info->email]);

            if (! $user->exists) {
                $user->email_verified_at = $user_info->email_verified ? now() : null;
            }
        }

        $user->mapOIDCUserinfo($issuer, $user_info, $mapping);
        $user->save();

        $mapping->user()->associate($user);
        $mapping->save();

        // Temporarily store the OIDC UserInfo in the user model
        $user->oidcUserInfo = $user_info;

        return $user;
    }

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
