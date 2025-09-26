<?php

namespace Maicol07\OIDCClient\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Maicol07\OIDCClient\Auth\OIDCGuard;

class OIDCController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * @throws Exception
     */
    final public function login(): RedirectResponse
    {
        return redirect()->away($this->guard()->getAuthorizationUrl());
    }

    /**
     * @throws Exception
     */
    final public function callback(Request $request): ?RedirectResponse
    {
        $user = $this->guard()->generateUser();

        $this->guard()->login($user);
        if ($this->guard()->check()) {
            $request->session()->regenerate();

            return redirect()->intended(config('oidc.redirect_path_after_login'));
        }

        abort(Response::HTTP_UNAUTHORIZED, trans('auth.failed'));
    }

    final public function logout(Request $request): RedirectResponse
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect()->intended(config('oidc.redirect_path_after_logout'));
    }

    private function guard(): OIDCGuard
    {
        $guard = auth()->guard(config('oidc.guard'));
        assert($guard instanceof OIDCGuard);

        return $guard;
    }
}
