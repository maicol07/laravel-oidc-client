<?php

/** @noinspection InterfacesAsConstructorDependenciesInspection */

namespace Maicol07\OIDCClient\Auth;

use Exception;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;
use Maicol07\OIDCClient\Models\User;
use Maicol07\OpenIDConnect\Client;
use Maicol07\OpenIDConnect\UserInfo;

class OIDCGuard extends SessionGuard
{
    private Client $oidc;

    #[Pure]
    public function __construct($name, Client $oidc, OIDCUserProvider $provider, Session $session, Request $request = null)
    {
        parent::__construct($name, $provider, $session, $request);
        $this->oidc = $oidc;
    }

    /**
     * @throws Exception
     */
    final public function getAuthorizationUrl(): string
    {
        return $this->oidc->getAuthorizationUrl(config('oidc.authorization_endpoint_query_params'), csrf_token());
    }

    /**
     * @throws Exception
     */
    final public function getUserInfo(): UserInfo
    {
        $this->oidc->authenticate();
        return $this->oidc->getUserInfo();
    }

    /**
     * @throws Exception
     */
    final public function generateUser(?UserInfo $user_info = null): User
    {
        if ($user_info === null) {
            $user_info = $this->getUserInfo();
        }
        return $this->provider->retrieveByInfo($user_info);
    }

    final public function login(User|Authenticatable $user, $remember = false): bool
    {
        $this->updateSession($user);

        if ($remember) {
            $this->ensureRememberTokenIsSet($user);
            $this->queueRecallerCookie($user);
        }
        $this->fireLoginEvent($user, $remember);

        /** @noinspection UnusedFunctionResultInspection */
        $this->setUser($user);
        return true;
    }

    final public function user(): Authenticatable|User|null
    {
        if ($this->loggedOut) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = $this->session->get($this->getName());

        if (!is_null($user) && $this->user = $user) {
            $this->fireAuthenticatedEvent($this->user);
        }

        if (is_null($this->user) && !is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user);

                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }

    /** @param User $user
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    final protected function updateSession($user): void
    {
        $this->session->put($this->getName(), $user);
        $this->session->migrate(true);
    }
}
