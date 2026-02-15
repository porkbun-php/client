<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\GlueRecordCollection;
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

        return GlueRecordCollection::fromArray($responseGlue);
    }

    /** @param array<string> $ips */
    public function create(string $subdomain, array $ips): void
    {
        $this->post("/domain/createGlue/{$this->domain}/{$subdomain}", $this->buildIpData($ips));
    }

    /** @param array<string> $ips */
    public function update(string $subdomain, array $ips): void
    {
        $this->post("/domain/updateGlue/{$this->domain}/{$subdomain}", $this->buildIpData($ips));
    }

    public function delete(string $subdomain): void
    {
        $this->post("/domain/deleteGlue/{$this->domain}/{$subdomain}");
    }

    /**
     * @param array<string> $ips
     *
     * @return array<string, string>
     */
    private function buildIpData(array $ips): array
    {
        $data = [];
        foreach (array_values($ips) as $index => $ip) {
            $data['ip' . ($index + 1)] = $ip;
        }

        return $data;
    }
}
