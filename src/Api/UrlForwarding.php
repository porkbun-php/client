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

    public function create(
        string $location,
        string $type,
        string $subdomain = '',
        string $includePath = 'no',
        string $wildcard = 'no',
    ): void {
        $data = [
            'subdomain' => $subdomain,
            'location' => $location,
            'type' => $type,
            'includePath' => $includePath,
            'wildcard' => $wildcard,
        ];

        $this->post("/domain/addUrlForward/{$this->domain}", $data);
    }

    public function delete(int $recordId): void
    {
        $this->post("/domain/deleteUrlForward/{$this->domain}/{$recordId}");
    }
}
