<?php

namespace Maicol07\OIDCClient;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Maicol07\OIDCClient\Auth\OIDCGuard;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
use Override;

class OIDCServiceProvider extends ServiceProvider
{
    /**
     * Config file path
     */
    private const string CONFIG_FILE = __DIR__.'/../config/oidc.php';

    /**
     * Register services.
     */
    #[Override]
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

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        Auth::extend('oidc', static function (Application $app): OIDCGuard {
            $provider = new OIDCUserProvider;

            return new OIDCGuard(
                'oidc',
                $provider,
                $app['session.store'],
                $app
            );
        });
    }
}
