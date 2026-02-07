<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Porkbun API Key
    |--------------------------------------------------------------------------
    |
    | Your Porkbun API key. You can generate one at:
    | https://porkbun.com/account/api
    |
    */
    'api_key' => env('PORKBUN_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Porkbun Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Porkbun API secret key. This is provided alongside the API key.
    |
    */
    'secret_key' => env('PORKBUN_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoint
    |--------------------------------------------------------------------------
    |
    | The API endpoint to use. Options are:
    | - 'default': https://api.porkbun.com (dual-stack, supports IPv4 and IPv6)
    | - 'ipv4': https://api-ipv4.porkbun.com (IPv4 only)
    |
    | The endpoint can still be changed at runtime via:
    | Porkbun::useIpv4Endpoint() or Porkbun::useDefaultEndpoint()
    |
    */
    'endpoint' => env('PORKBUN_ENDPOINT', 'default'),
];
