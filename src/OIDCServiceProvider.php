<?php

namespace Maicol07\OIDCClient;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Maicol07\OIDCClient\Auth\OIDCGuard;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
use Maicol07\OIDCClient\Http\OIDCStateMiddleware;
use Maicol07\OpenIDConnect\Client;
use Maicol07\OpenIDConnect\ClientAuthMethod;
use Maicol07\OpenIDConnect\CodeChallengeMethod;
use Maicol07\OpenIDConnect\ResponseType;
use Maicol07\OpenIDConnect\Scope;

class OIDCServiceProvider extends ServiceProvider
{
    /**
     * Config file path
     */
    private const string CONFIG_FILE = __DIR__ . '/../config/oidc.php';

    /**
     * Register services.
     */
    #[\Override]
    final public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_FILE, 'oidc');
    }

    /**
     * Bootstrap services.
     */
    final public function boot(): void
    {
        $this->publishes([
           self::CONFIG_FILE => config_path('oidc.php'),
        ], 'oidc.config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->shouldEnableOIDCStateMiddleware()) {
            $this->registerOIDCStateMiddleware(
                $this->app->make(Router::class)
            );
        }

        Auth::extend('oidc', function (array $app): OIDCGuard {
            $client = $this->getOIDCClient();
            $provider = new OIDCUserProvider();
            return new OIDCGuard(
                'oidc',
                $client,
                $provider,
                $app['session.store']
            );
        });
    }

    private function getOIDCClient(): Client
    {
        $config = config('oidc');
        return new Client(
            client_id: $config->get('client_id'),
            client_secret: $config->get('client_secret'),
            provider_url: $config->get('provider_url'),
            issuer: $config->get('issuer'),
            scopes: array_map(static fn (string $scope): Scope => Scope::from($scope), $config->get('scopes')),
            redirect_uri: route('oidc.callback'),
            enable_pkce: $config->get('enable_pkce'),
            enable_nonce: $config->get('enable_nonce'),
            code_challenge_method: CodeChallengeMethod::from($config->get('code_challenge_method')),
            time_drift: $config->get('time_drift'),
            response_types: array_map(static fn (string $type): ResponseType => ResponseType::from($type), $config->get('response_types')),
            id_token_signing_alg_values_supported: $config->get('id_token_signing_alg_values_supported'),
            authorization_endpoint: $config->get('authorization_endpoint'),
            token_endpoint: $config->get('token_endpoint'),
            userinfo_endpoint: $config->get('userinfo_endpoint'),
            end_session_endpoint: $config->get('end_session_endpoint'),
            registration_endpoint: $config->get('registration_endpoint'),
            introspect_endpoint: $config->get('introspect_endpoint'),
            revocation_endpoint: $config->get('revocation_endpoint'),
            jwks_endpoint: $config->get('jwks_endpoint'),
            authorization_response_iss_parameter_supported: $config->get('authorization_response_iss_parameter_supported'),
            token_endpoint_auth_methods_supported: array_map(static fn (string $method): ClientAuthMethod => ClientAuthMethod::from($method), $config->get('token_endpoint_auth_methods_supported')),
            http_proxy: $config->get('http_proxy'),
            cert_path: $config->get('cert_path'),
            verify_ssl: $config->get('verify'),
            timeout: $config->get('timeout'),
            client_name: $config->get('client_name'),
            allow_implicit_flow: $config->get('allow_implicit_flow'),
            jwks: $config->get('jwks')
        );
    }

    private function getWebMiddlewareGroup(Router $router): Collection
    {
        $groups = $router->getMiddlewareGroups();

        return collect($groups['web'] ?? []);
    }

    private function registerOIDCStateMiddleware(Router $router): void
    {
        $group = $this->getWebMiddlewareGroup($router);
        $index = $group->search(VerifyCsrfToken::class, true);

        if (false !== $index) {
            $group->splice($index, 1, [OIDCStateMiddleware::class, VerifyCsrfToken::class]);
            $router->middlewareGroup('web', $group->toArray());
        }
    }

    private function shouldEnableOIDCStateMiddleware(): bool
    {
        $request = Request::capture();
        $callbackPathInfo = '/' . config('oidc.provider_name') . '/' . config('oidc.callback_route_path');

        return !config('oidc.disable_state_middleware_for_post_callback', false)
            && strtoupper($request->method()) === 'POST'
            && $request->getPathInfo() === $callbackPathInfo;
    }
}
