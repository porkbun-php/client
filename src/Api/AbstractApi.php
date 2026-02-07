<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\HttpClient;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractApi
{
    public function __construct(
        protected readonly HttpClient $httpClient,
    ) {
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->httpClient->getLastResponse();
    }

    protected function post(string $path, array $data = []): array
    {
        return $this->httpClient->post($path, $data);
    }

    protected function get(string $path, array $params = []): array
    {
        return $this->httpClient->get($path, $params);
    }
}
