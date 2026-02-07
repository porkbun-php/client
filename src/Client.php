<?php

declare(strict_types=1);

namespace Porkbun;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Porkbun\Api\Dns;
use Porkbun\Api\Domain;
use Porkbun\Api\Ping;
use Porkbun\Api\Pricing;
use Porkbun\Api\Ssl;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Client
{
    private ?HttpClient $httpClient = null;

    private ?string $apiKey = null;

    private ?string $secretKey = null;

    private Endpoint $endpoint = Endpoint::DEFAULT;

    public function __construct(private readonly ClientInterface $psrClient, private readonly RequestFactoryInterface $requestFactory, private readonly StreamFactoryInterface $streamFactory)
    {
    }

    public static function create(
        ?string $apiKey = null,
        ?string $secretKey = null,
    ): self {
        $client = new self(
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $hasApiKey = $apiKey !== null;
        $hasSecretKey = $secretKey !== null;

        if ($hasApiKey !== $hasSecretKey) {
            throw new InvalidArgumentException('Both apiKey and secretKey must be provided together, or neither');
        }

        if ($hasApiKey && $hasSecretKey) {
            $client->authenticate($apiKey, $secretKey);
        }

        return $client;
    }

    public function authenticate(string $apiKey, string $secretKey): self
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->httpClient = null;

        return $this;
    }

    public function clearAuth(): self
    {
        $this->apiKey = null;
        $this->secretKey = null;
        $this->httpClient = null;

        return $this;
    }

    public function isAuthenticated(): bool
    {
        return $this->apiKey !== null && $this->secretKey !== null;
    }

    public function useIpv4Endpoint(): self
    {
        $this->endpoint = Endpoint::IPV4;
        $this->httpClient = null;

        return $this;
    }

    public function useDefaultEndpoint(): self
    {
        $this->endpoint = Endpoint::DEFAULT;
        $this->httpClient = null;

        return $this;
    }

    public function useEndpoint(Endpoint $endpoint): self
    {
        $this->endpoint = $endpoint;
        $this->httpClient = null;

        return $this;
    }

    public function getEndpoint(): Endpoint
    {
        return $this->endpoint;
    }

    public function pricing(): Pricing
    {
        return new Pricing($this->getHttpClient());
    }

    public function ping(): Ping
    {
        return new Ping($this->getHttpClient());
    }

    public function domains(): Domain
    {
        return new Domain($this->getHttpClient());
    }

    public function dns(string $domain): Dns
    {
        return new Dns($this->getHttpClient(), $domain);
    }

    public function ssl(string $domain): Ssl
    {
        return new Ssl($this->getHttpClient(), $domain);
    }

    private function getHttpClient(): HttpClient
    {
        if (!$this->httpClient instanceof HttpClient) {
            $this->httpClient = new HttpClient(
                $this->psrClient,
                $this->requestFactory,
                $this->streamFactory,
                $this->endpoint->getUrl(),
                $this->apiKey,
                $this->secretKey,
            );
        }

        return $this->httpClient;
    }
}
