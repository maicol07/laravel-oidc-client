<?php

namespace Maicol07\OIDCClient\Http;

use Closure;
use Illuminate\Http\Request;

class OIDCStateMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        if ($request->has('state')) {
            $request->headers->add(['X-CSRF-TOKEN' => $request->get('state')]);
            $request->session()->put('_token', $request->get('state'));
        }

        return $next($request);
    }

}
