<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\NameserverCollection;
use Porkbun\DTO\OperationResult;
use Porkbun\Internal\ClientContext;

final class Nameservers extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function all(): NameserverCollection
    {
        $response = $this->post("/domain/getNs/{$this->domain}");

        /** @var array<string> $nameservers */
        $nameservers = $response['ns'] ?? [];

        return new NameserverCollection($nameservers);
    }

    public function update(string $nameserver, string ...$moreNameservers): OperationResult
    {
        $allNameservers = [$nameserver, ...$moreNameservers];
        $data = [];
        foreach (array_values($allNameservers) as $index => $ns) {
            $data['ns' . ($index + 1)] = $ns;
        }

        $response = $this->post("/domain/updateNs/{$this->domain}", $data);

        return OperationResult::fromArray($response);
    }
}
