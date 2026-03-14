<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\GlueRecordCollection;
use Porkbun\DTO\OperationResult;
use Porkbun\Internal\ClientContext;

final class GlueRecords extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function all(): GlueRecordCollection
    {
        $response = $this->post("/domain/getGlue/{$this->domain}");

        /** @var array<array<string, mixed>> $responseGlue */
        $responseGlue = $response['glue'] ?? [];

        foreach ($responseGlue as &$record) {
            $record['host'] = self::stripDomainSuffix((string) ($record['host'] ?? ''), $this->domain);
        }

        return GlueRecordCollection::fromArray($responseGlue);
    }

    public function create(string $subdomain, string $ip, string ...$moreIps): OperationResult
    {
        $response = $this->post("/domain/createGlue/{$this->domain}/{$subdomain}", $this->buildIpData($ip, ...$moreIps));

        return OperationResult::fromArray($response);
    }

    public function update(string $subdomain, string $ip, string ...$moreIps): OperationResult
    {
        $response = $this->post("/domain/updateGlue/{$this->domain}/{$subdomain}", $this->buildIpData($ip, ...$moreIps));

        return OperationResult::fromArray($response);
    }

    public function delete(string $subdomain): OperationResult
    {
        $response = $this->post("/domain/deleteGlue/{$this->domain}/{$subdomain}");

        return OperationResult::fromArray($response);
    }

    /** @return array<string, string> */
    private function buildIpData(string $ip, string ...$moreIps): array
    {
        $allIps = [$ip, ...$moreIps];
        $data = [];
        foreach (array_values($allIps) as $index => $ipAddr) {
            $data['ip' . ($index + 1)] = $ipAddr;
        }

        return $data;
    }
}
