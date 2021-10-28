<?php

namespace Maicol07\OIDCClient;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Maicol07\OIDCClient\Auth\OIDCGuard;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
use Maicol07\OpenIDConnect\Client;

class OIDCServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    final public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/oidc.php', 'oidc');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    final public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/oidc.php' => config_path('oidc.php'),
        ], 'oidc.config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

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
        $config = collect(config('oidc'));
        $config->replace([
            'redirect_uri' => $config->get('redirect_uri', fn () => url())()
        ]);
        return new Client($config->all());
    }
}
