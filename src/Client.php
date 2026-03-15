<?php

declare(strict_types=1);

namespace Porkbun;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Porkbun\Api\Domains;
use Porkbun\Api\Pricing;
use Porkbun\DTO\PingResult;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Internal\ClientContext;
use Porkbun\Resource\Domain;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Client
{
    public private(set) Endpoint $endpoint = Endpoint::DEFAULT;

    private ?HttpClient $httpClient = null;

    private ?string $apiKey = null;

    private ?string $secretKey = null;

    private readonly ClientInterface $psrClient;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    private readonly ClientContext $clientContext;

    public function __construct(?ClientInterface $httpClient = null)
    {
        try {
            $this->psrClient = $httpClient ?? Psr18ClientDiscovery::find();
            $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
            $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        } catch (NotFoundException $e) {
            throw new InvalidArgumentException(
                'No PSR-18 HTTP client found. Install one, e.g.: composer require guzzlehttp/guzzle',
                $e->getCode(),
                previous: $e,
            );
        }

        $this->clientContext = new ClientContext(fn (): HttpClient => $this->getHttpClient());
    }

    private function __clone(): void
    {
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

    public function pricing(): Pricing
    {
        return new Pricing($this->clientContext);
    }

    public function ping(): PingResult
    {
        $data = $this->clientContext->httpClient()->post('/ping');

        return PingResult::fromArray($data);
    }

    public function domains(): Domains
    {
        return new Domains($this->clientContext);
    }

    public function domain(string $domain): Domain
    {
        return new Domain($domain, $this->clientContext, $this->domains());
    }

    private function getHttpClient(): HttpClient
    {
        if (!$this->httpClient instanceof HttpClient) {
            $this->httpClient = new HttpClient(
                $this->psrClient,
                $this->requestFactory,
                $this->streamFactory,
                $this->endpoint->url(),
                $this->apiKey,
                $this->secretKey,
            );
        }

        return $this->httpClient;
    }
}
