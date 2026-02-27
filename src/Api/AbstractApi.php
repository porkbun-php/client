<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\Internal\ClientContext;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractApi
{
    public function __construct(
        protected readonly ClientContext $context,
    ) {
    }

    /**
     * Returns the last HTTP response received by this service's HTTP client.
     *
     * Note: Returns null if no requests have been made, or if the Client's
     * configuration was changed (authenticate(), clearAuth(), useIpv4Endpoint(),
     * etc.) since the last request — configuration changes recreate the
     * underlying HTTP client.
     */
    public function lastResponse(): ?ResponseInterface
    {
        return $this->context->httpClient()->lastResponse;
    }

    protected function post(string $path, array $data = []): array
    {
        return $this->context->httpClient()->post($path, $data);
    }

    protected function httpGet(string $path, array $params = []): array
    {
        return $this->context->httpClient()->get($path, $params);
    }
}
