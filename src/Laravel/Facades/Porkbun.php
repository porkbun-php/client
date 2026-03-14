<?php

declare(strict_types=1);

namespace Porkbun\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Override;
use Porkbun\Api\Domains;
use Porkbun\Api\Pricing;
use Porkbun\Client;
use Porkbun\DTO\PingResult;
use Porkbun\Enum\Endpoint;
use Porkbun\Resource\Domain;

/**
 * @method static Client authenticate(string $apiKey, string $secretKey)
 * @method static Client clearAuth()
 * @method static bool isAuthenticated()
 * @method static Client useIpv4Endpoint()
 * @method static Client useDefaultEndpoint()
 * @method static Client useEndpoint(Endpoint $endpoint)
 * @method static Pricing pricing()
 * @method static PingResult ping()
 * @method static Domains domains()
 * @method static Domain domain(string $domain)
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
