<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\NameserverCollection;
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

    /** @param array<string> $nameservers */
    public function update(array $nameservers): void
    {
        $data = [];
        foreach (array_values($nameservers) as $index => $nameserver) {
            $data['ns' . ($index + 1)] = $nameserver;
        }

        $this->post("/domain/updateNs/{$this->domain}", $data);
    }
}
