<?php

return [
    'client_id'     => env('OIDC_CLIENT_ID'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),
    'provider_url'  => env('OIDC_PROVIDER_URL'),
    'provider_name' => env('OIDC_PROVIDER_NAME'),
    'scopes'        => ['openid']
];