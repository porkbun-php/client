<?php

declare(strict_types=1);

namespace Porkbun\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Override;
use Porkbun\Api\Dns;
use Porkbun\Api\Domain;
use Porkbun\Api\Ping;
use Porkbun\Api\Pricing;
use Porkbun\Api\Ssl;
use Porkbun\Client;
use Porkbun\Enum\Endpoint;

/**
 * @method static Client authenticate(string $apiKey, string $secretKey)
 * @method static Client clearAuth()
 * @method static bool isAuthenticated()
 * @method static Client useIpv4Endpoint()
 * @method static Client useDefaultEndpoint()
 * @method static Client useEndpoint(Endpoint $endpoint)
 * @method static Endpoint getEndpoint()
 * @method static Pricing pricing()
 * @method static Ping ping()
 * @method static Domain domains()
 * @method static Dns dns(string $domain)
 * @method static Ssl ssl(string $domain)
 *
 * @see Client
 */
final class Porkbun extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
