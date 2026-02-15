<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\UrlForwardCollection;
use Porkbun\Internal\ClientContext;

final class UrlForwarding extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function all(): UrlForwardCollection
    {
        $response = $this->post("/domain/getUrlForwarding/{$this->domain}");

        /** @var array<array<string, mixed>> $responseForwards */
        $responseForwards = $response['forwards'] ?? [];

        return UrlForwardCollection::fromArray($responseForwards);
    }

    /** @param array{subdomain?: string, location: string, type: string, includePath?: string, wildcard?: string} $params */
    public function add(array $params): void
    {
        $this->post("/domain/addUrlForward/{$this->domain}", $params);
    }

    public function delete(int|string $recordId): void
    {
        $this->post("/domain/deleteUrlForward/{$this->domain}/{$recordId}");
    }
}
