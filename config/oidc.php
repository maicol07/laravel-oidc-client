<?php

/** @noinspection LaravelFunctionsInspection */
/** @noinspection JsonEncodingApiUsageInspection */
/** @see \Maicol07\OpenIDConnect\Client */
return [
    // Client details
    'client_id' => env('OIDC_CLIENT_ID'),
    'client_name' => env('OIDC_CLIENT_NAME', 'OpenID Connect Client'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),

    // Provider details
    'provider_url' => env('OIDC_PROVIDER_URL'),
    'issuer' => env('OIDC_ISSUER'),

    // Endpoints
    'authorization_endpoint' => env('OIDC_AUTHORIZATION_ENDPOINT'),
    'end_session_endpoint' => env('OIDC_END_SESSION_ENDPOINT'),
    'registration_endpoint' => env('OIDC_REGISTRATION_ENDPOINT'),
    'introspect_endpoint' => env('OIDC_INTROSPECT_ENDPOINT'),
    'revocation_endpoint' => env('OIDC_REVOCATION_ENDPOINT'),
    'jwks_endpoint' => env('OIDC_JWKS_ENDPOINT'),
    'token_endpoint' => env('OIDC_TOKEN_ENDPOINT'),
    'userinfo_endpoint' => env('OIDC_USERINFO_ENDPOINT'),

    // OIDC options
    'allow_implicit_flow' => env('OIDC_ALLOW_IMPLICIT_FLOW', false),
    'enable_nonce' => env('OIDC_ENABLE_NONCE', true),
    'scopes' => explode(' ', env('OIDC_SCOPES', 'openid')),

    // Authorization endpoint options
    'authorization_endpoint_query_params' => json_decode(env('OIDC_AUTHORIZATION_ENDPOINT_QUERY_PARAMS', 'null'), true),
    'authorization_response_iss_parameter_supported' => env('OIDC_AUTHORIZATION_RESPONSE_ISS_PARAMETER_SUPPORTED', false),
    'code_challenge_method' => env('OIDC_CODE_CHALLENGE_METHOD', 'plain'),
    'enable_pkce' => env('OIDC_ENABLE_PKCE', true),
    'response_types' => explode(' ', env('OIDC_RESPONSE_TYPES', '')),

    // Token endpoint options
    'id_token_signing_alg_values_supported' => explode(' ', env('OIDC_ID_TOKEN_SIGNING_ALG_VALUES_SUPPORTED', '')),
    'jwks' => null,
    'jwt_audience' => env('OIDC_JWT_AUDIENCE'),
    'time_drift' => env('OIDC_TIME_DRIFT', 300),
    'token_endpoint_auth_methods_supported' => explode(' ', env('OIDC_TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED', '')),

    // Http options
    'http_proxy' => env('OIDC_HTTP_PROXY'),
    'cert_path' => env('OIDC_CERT_PATH'),
    'verify' => env('OIDC_VERIFY', true),
    'timeout' => env('OIDC_TIMEOUT', 0),

    // Routes
    'callback_route_path' => env('OIDC_CALLBACK_ROUTE_PATH', 'callback'),
    'redirect_path_after_login' => env('OIDC_REDIRECT_PATH_AFTER_LOGIN', '/'),
    'redirect_path_after_logout' => env('OIDC_REDIRECT_PATH_AFTER_LOGOUT', '/'),
    'disable_state_middleware_for_post_callback' => env('OIDC_DISABLE_STATE_MIDDLEWARE_FOR_POST_CALLBACK', false),
];
