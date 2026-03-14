<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\OperationResult;
use Porkbun\DTO\UrlForwardCollection;
use Porkbun\Enum\UrlForwardType;
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
        string|UrlForwardType $type,
        string $subdomain = '',
        bool $includePath = false,
        bool $wildcard = false,
    ): OperationResult {
        $resolvedType = UrlForwardType::resolve($type)->value;

        $data = [
            'subdomain' => $subdomain,
            'location' => $location,
            'type' => $resolvedType,
            'includePath' => $includePath ? 'yes' : 'no',
            'wildcard' => $wildcard ? 'yes' : 'no',
        ];

        $response = $this->post("/domain/addUrlForward/{$this->domain}", $data);

        return OperationResult::fromArray($response);
    }

    public function delete(int $id): OperationResult
    {
        $response = $this->post("/domain/deleteUrlForward/{$this->domain}/{$id}");

        return OperationResult::fromArray($response);
    }
}
