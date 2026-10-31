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

        /** @var list<array{0?: string, 1?: array<mixed>}> $hosts */
        $hosts = $response['hosts'] ?? [];

        $records = [];
        foreach ($hosts as $entry) {
            $host = $entry[0] ?? '';
            if ($host === '') {
                continue;
            }

            $records[] = [
                'host' => self::stripDomainSuffix($host, $this->domain),
                'ips' => self::flattenIps($entry[1] ?? []),
            ];
        }

        return GlueRecordCollection::fromArray($records);
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

    /**
     * @param array<mixed> $ips
     *
     * @return list<string>
     */
    private static function flattenIps(array $ips): array
    {
        // The API sends either a flat list or {v4, v6}.
        if (!array_is_list($ips)) {
            $ips = [...(array) ($ips['v4'] ?? []), ...(array) ($ips['v6'] ?? [])];
        }

        return array_values(array_map(strval(...), array_filter($ips, is_scalar(...))));
    }

    /** @return array{ips: list<string>} */
    private function buildIpData(string $ip, string ...$moreIps): array
    {
        return ['ips' => array_values([$ip, ...$moreIps])];
    }
}
