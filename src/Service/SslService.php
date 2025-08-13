<?php

declare(strict_types=1);

namespace Porkbun\Service;

use Porkbun\Config;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class SslService extends AbstractService
{
    public function __construct(
        HttpClientInterface $httpClient,
        Config $config,
        private string $domain
    ) {
        parent::__construct($httpClient, $config);
    }

    public function retrieve(): array
    {
        return $this->post("/ssl/retrieve/{$this->domain}");
    }

    protected function requiresAuth(): bool
    {
        return true;
    }
}
