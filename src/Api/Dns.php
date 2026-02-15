<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DnssecRecordCollection;
use Porkbun\Internal\ClientContext;

final class Dns extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function all(): DnsRecordCollection
    {
        $response = $this->post("/dns/retrieve/{$this->domain}");

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        /** @var ?string $cloudflare */
        $cloudflare = $response['cloudflare'] ?? null;

        return DnsRecordCollection::fromArray($records, $cloudflare);
    }

    public function find(int $id): ?DnsRecord
    {
        $response = $this->post("/dns/retrieve/{$this->domain}/{$id}");

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        if ($records === []) {
            return null;
        }

        return DnsRecord::fromArray($records[0]);
    }

    public function findByType(string $type, ?string $name = null): DnsRecordCollection
    {
        $endpoint = "/dns/retrieveByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $response = $this->post($endpoint);

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        /** @var ?string $cloudflare */
        $cloudflare = $response['cloudflare'] ?? null;

        return DnsRecordCollection::fromArray($records, $cloudflare);
    }

    public function create(
        string $name,
        string $type,
        string $content,
        int|string $ttl = 600,
        int|string $prio = 0,
        string $notes = '',
    ): CreateResult {
        $data = [
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => (string) $ttl,
            'prio' => (string) $prio,
        ];

        if ($notes !== '') {
            $data['notes'] = $notes;
        }

        $response = $this->post("/dns/create/{$this->domain}", $data);

        return CreateResult::fromArray($response);
    }

    public function createFromBuilder(DnsRecordBuilder $dnsRecordBuilder): CreateResult
    {
        $data = $dnsRecordBuilder->getData();

        $response = $this->post("/dns/create/{$this->domain}", $data);

        return CreateResult::fromArray($response);
    }

    public function record(): DnsRecordBuilder
    {
        return new DnsRecordBuilder();
    }

    /** @param array<string, mixed> $data */
    public function edit(int $id, array $data): void
    {
        $this->post("/dns/edit/{$this->domain}/{$id}", $data);
    }

    /** @param array<string, mixed> $data Updates ALL matching records */
    public function update(string $type, ?string $name, array $data): void
    {
        $endpoint = "/dns/editByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $this->post($endpoint, $data);
    }

    public function delete(int $id): void
    {
        $this->post("/dns/delete/{$this->domain}/{$id}");
    }

    public function deleteByType(string $type, ?string $name = null): void
    {
        $endpoint = "/dns/deleteByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $this->post($endpoint);
    }

    /** @param array{keyTag: string, alg: string, digestType: string, digest: string, keyDataFlags?: string, keyDataProtocol?: string, keyDataAlgo?: string, keyDataPubKey?: string, maxSigLife?: string} $params */
    public function createDnssec(array $params): void
    {
        $this->post("/dns/createDnssecRecord/{$this->domain}", $params);
    }

    public function getDnssecRecords(): DnssecRecordCollection
    {
        $response = $this->post("/dns/getDnssecRecords/{$this->domain}");

        /** @var array<array<string, mixed>> $responseRecords */
        $responseRecords = $response['records'] ?? [];

        return DnssecRecordCollection::fromArray($responseRecords);
    }

    public function deleteDnssec(string $keyTag): void
    {
        $this->post("/dns/deleteDnssecRecord/{$this->domain}/{$keyTag}");
    }
}
