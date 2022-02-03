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

class OIDCServiceProvider extends ServiceProvider
{
    /**
     * Config file path
     */
    private const CONFIG_FILE = __DIR__ . '/../config/oidc.php';

    /**
     * Register services.
     *
     * @return void
     */
    final public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_FILE, 'oidc');
    }

    /**
     * Bootstrap services.
     *
     * @return void
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

        Auth::extend('oidc', function ($app) {
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
        $config = collect(config('oidc'))
            ->put('redirect_uri', route('oidc.callback'));
        return new Client($config->all());
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
