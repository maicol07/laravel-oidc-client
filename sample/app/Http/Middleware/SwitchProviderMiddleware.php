<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SwitchProviderMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->query->has('switch_provider')) {
            if ($request->session()->exists('switch_oidc_provider')) {
                $request->session()->forget('switch_oidc_provider');
            } else {
                $request->session()->put('switch_oidc_provider');
            }

            return redirect($request->fullUrlWithoutQuery('switch_provider'));
        }

        if ($request->session()->exists('switch_oidc_provider')) {
            config([
                'oidc.provider_url' => 'oidc2:3000/case/generic',
                'oidc.issuer' => 'oidc2.localhost',
                'oidc.jwt_audience' => 'http://laravel.localhost/oidc/callback',
            ]);
        }

        return $next($request);
    }
}
