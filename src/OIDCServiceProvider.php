<?php

namespace Maicol07\OIDCClient;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Maicol07\OIDCClient\Auth\OIDCGuard;
use Maicol07\OIDCClient\Auth\OIDCUserProvider;
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
        $this->autodiscovery();

        $config = collect(config('oidc'))
            ->put('redirect_uri', route('oidc.callback'));
        return new Client($config->all());
    }

    private function autodiscovery(): void {
        if (!is_null($providerURL = config('oidc.provider_url'))) {
            $config = Http::get($providerURL)->json();
            config()->set('oidc', array_merge(config('oidc'), $config ?? []));
        }
    }
}
