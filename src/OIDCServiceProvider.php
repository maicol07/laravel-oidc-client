<?php

namespace Maicol07\OIDCClient;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;
use Maicol07\OIDCClient\Auth\OIDCGuard;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
use Maicol07\OIDCClient\Http\OIDCStateMiddleware;
use Maicol07\OpenIDConnect\Client;
use Maicol07\OpenIDConnect\ClientAuthMethod;
use Maicol07\OpenIDConnect\CodeChallengeMethod;
use Maicol07\OpenIDConnect\JwtSigningAlgorithm;
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

        Auth::extend('oidc', static function (Application $app, string $name, array $config): OIDCGuard {
            $provider = new OIDCUserProvider();
            return new OIDCGuard(
                'oidc',
                $provider,
                $app['session.store'],
                $app
            );
        });
    }
}
