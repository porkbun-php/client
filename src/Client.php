<?php

declare(strict_types=1);

namespace Porkbun;

use Http\Discovery\Psr18ClientDiscovery;
use Porkbun\Service\AuthService;
use Porkbun\Service\DnsService;
use Porkbun\Service\DomainService;
use Porkbun\Service\PricingService;
use Porkbun\Service\SslService;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class Client
{
    private Config $config;

    private HttpClientInterface $httpClient;

    public function __construct(?Config $config = null, ?HttpClientInterface $httpClient = null)
    {
        $this->config = $config ?? new Config();
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->config->setBaseUrl($baseUrl);

        return $this;
    }

    public function setAuth(string $apiKey, string $secretKey): self
    {
        $this->config->setAuth($apiKey, $secretKey);

        return $this;
    }

    public function clearAuth(): self
    {
        $this->config->clearAuth();

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function pricing(): PricingService
    {
        return new PricingService($this->httpClient, $this->config);
    }

    public function auth(): AuthService
    {
        return new AuthService($this->httpClient, $this->config);
    }

    public function domains(): DomainService
    {
        return new DomainService($this->httpClient, $this->config);
    }

    public function dns(string $domain): DnsService
    {
        return new DnsService(
            httpClient: $this->httpClient,
            config: $this->config,
            domain: $domain
        );
    }

    public function ssl(string $domain): SslService
    {
        return new SslService(
            httpClient: $this->httpClient,
            config: $this->config,
            domain: $domain
        );
    }
}
