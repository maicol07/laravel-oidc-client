# OIDC Client

A Laravel package for delegating authentication to an OpenID Provider.

> This package is an heavenly modified fork of [cabinetoffice / oidc-client — Bitbucket](https://bitbucket.org/cabinetoffice/oidc-client)

## Requirements

- PHP 8.0+
- Laravel 8+
- Composer 2

## Installation

Begin by adding this package to your depedencies with the command:

```powershell
composer require maicol07/laravel-oidc-client
```

If you have opted out from auto discovery, you'll need to add the following line to the list of registered service
providers in `config/app.php`:

```php
Maicol07\OIDCClient\OIDCServiceProvider::class
```

Edit your `config/auth.php` file to use OpenID as the authentication method for your users:

```php
'guards' => [
    'web' => [
        'driver' => 'oidc',
        ...
    ],
    ...
],
```

## Configuration

You can set the following environment variables to adjust the package settings:

- `OIDC_CLIENT_ID`: Client ID of your app. This is commonly provided by your OIDC provider.
- `OIDC_CLIENT_SECRET`: Client secret of your app. This is commonly provided by your OIDC provider.
- `OIDC_PROVIDER_URL`: URL of your OIDC provider. This is used if your provider supports OIDC Auto Discovery.
- `OIDC_PROVIDER_NAME`: This is a short name for your OpenID provider, which will only appears in your OpenID routes. Do
  not use spaces. Defaults to `oidc`
- `OIDC_CALLBACK_ROUTE_PATH`: A path (with or without leading slash) to append to the provider name, to make the
  callback route path. Defaults to `callback`
  Example with the default values: `oidc/callback` (`OIDC_PROVIDER_NAME` + `/` + `OIDC_CALLBACK_ROUTE_PATH`)
- `OIDC_VERIFY`: Verify SSL when sending requests to the server. Defaults to `true`. (Optional: You can
  set `OIDC_CERT_PATH` to an SSL certificate path if you set this option to `false`)
- `OIDC_HTTP_PROXY`: If you have a proxy, set it here.
- `OIDC_SCOPES`: A list of scopes, separated by a comma (`,`). Defaults to `['openid']`.
  Example of valid value: `openid,email`
- `OIDC_AUTHORIZATION_ENDPOINT_QUERY_PARAMS`: A list of query parameters to add to the authorization endpoint encoded as
  a JSON object.
  Example of valid value: `{"response_type":"code"}`
- `OIDC_DISABLE_STATE_MIDDLEWARE_FOR_POST_CALLBACK`: A boolean to disable the registration of the `OIDCStateMiddleware` middleware.  
  This middleware rebuilds the session token held in the `state` parameter of a `POST` request to the `callback` route.

You can find other options to set and their env variables in `config/oidc.php`. Note that some options are not
required (like endpoints) if you use OIDC auto discovery!

You can also publish the config file (`config/oidc.php`) if you want:

```powershell
php artisan vendor:publish --provider="Maicol07\OIDCClient\OIDCServiceProvider"
```

## How to use

Once everything is set up, you can replace your login system with a call to the route `route('oidc.login')`. For
logouts, use the route `route('oidc.logout')`.

You can set the following environment variables to specify the routes/URLs you want your users to be redirected to upon
successful authentication/logout: `OIDC_REDIRECT_PATH_AFTER_LOGIN` and `OIDC_REDIRECT_PATH_AFTER_LOGOUT`.

You may want to create your own `User` model. If yes, then you must extend `Maicol07\OIDCClient\User` in order to get
auth working.

Check your `auth.providers.users.model` config value: it must be set to your custom `User` model or
to `Maicol07\OIDCClient\User` instead.

---

> Originally developed by Cabinet Office Digital Development in October 2019.
>
> Currently maintained by [maicol07](https://maicol07.it) from October 2021
