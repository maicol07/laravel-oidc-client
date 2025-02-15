<?php

/** @noinspection InterfacesAsConstructorDependenciesInspection */

namespace Maicol07\OIDCClient\Auth;

use Exception;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Maicol07\OIDCClient\Models\User;
use Maicol07\OpenIDConnect\Client;
use Maicol07\OpenIDConnect\ClientAuthMethod;
use Maicol07\OpenIDConnect\CodeChallengeMethod;
use Maicol07\OpenIDConnect\JwtSigningAlgorithm;
use Maicol07\OpenIDConnect\ResponseType;
use Maicol07\OpenIDConnect\Scope;
use Maicol07\OpenIDConnect\UserInfo;
use Override;

class OIDCGuard extends SessionGuard
{
    private Client $oidc;

    /**
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function __construct(
        $name,
        OIDCUserProvider $provider,
        Session $session,
        private readonly Application $app,
        ?Request $request = null,
    ) {
        parent::__construct($name, $provider, $session, $request);
        $this->buildOIDCClient();
    }

    /**
     * @throws BindingResolutionException
     * @throws ConnectionException
     */
    public function buildOIDCClient(): void
    {
        $config = $this->app->make('config')->get('oidc');
        $this->oidc = new Client(
            client_id: $config['client_id'],
            client_secret: $config['client_secret'],
            provider_url: $config['provider_url'],
            issuer: $config['issuer'],
            scopes: array_map(static fn (string $scope): Scope => Scope::from($scope), array_filter($config['scopes'])),
            redirect_uri: route('oidc.callback'),
            enable_pkce: $config['enable_pkce'],
            enable_nonce: $config['enable_nonce'],
            code_challenge_method: CodeChallengeMethod::from($config['code_challenge_method']),
            time_drift: $config['time_drift'],
            response_types: array_map(static fn (string $type): ResponseType => ResponseType::from($type), array_filter($config['response_types'])),
            id_token_signing_alg_values_supported: array_map(static fn (string $alg): JwtSigningAlgorithm => JwtSigningAlgorithm::fromName($alg), array_filter($config['id_token_signing_alg_values_supported'])),
            authorization_endpoint: $config['authorization_endpoint'],
            token_endpoint: $config['token_endpoint'],
            userinfo_endpoint: $config['userinfo_endpoint'],
            end_session_endpoint: $config['end_session_endpoint'],
            registration_endpoint: $config['registration_endpoint'],
            introspect_endpoint: $config['introspect_endpoint'],
            revocation_endpoint: $config['revocation_endpoint'],
            jwks_endpoint: $config['jwks_endpoint'],
            jwt_audience: $config['jwt_audience'],
            authorization_response_iss_parameter_supported: $config['authorization_response_iss_parameter_supported'],
            token_endpoint_auth_methods_supported: array_map(static fn (string $method): ClientAuthMethod => ClientAuthMethod::from($method), array_filter($config['token_endpoint_auth_methods_supported'])),
            http_proxy: $config['http_proxy'],
            cert_path: $config['cert_path'],
            verify_ssl: $config['verify'],
            timeout: $config['timeout'],
            client_name: $config['client_name'],
            allow_implicit_flow: $config['allow_implicit_flow'],
            jwks: $config['jwks']
        );
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

        assert($this->provider instanceof OIDCUserProvider);

        return $this->provider->retrieveByInfo($user_info);
    }

    #[Override]
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

    #[Override]
    final public function user(): Authenticatable|User|null
    {
        if ($this->loggedOut) {
            return null;
        }

        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = $this->session->get($this->getName());

        if (! is_null($user) && $this->user = $user) {
            $this->fireAuthenticatedEvent($this->user);
        }

        if (is_null($this->user) && ! is_null($recaller = $this->recaller())) {
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
    #[Override]
    final protected function updateSession($user): void
    {
        $this->session->put($this->getName(), $user);
        $this->session->migrate(true);
    }
}
