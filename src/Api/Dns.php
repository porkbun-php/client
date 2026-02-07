<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateDnsRecordData;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DnssecRecord;
use Porkbun\HttpClient;

final class Dns extends AbstractApi
{
    public function __construct(
        HttpClient $httpClient,
        private readonly string $domain,
    ) {
        parent::__construct($httpClient);
    }

    public function create(
        string $name,
        string $type,
        string $content,
        int|string $ttl = 600,
        int|string $prio = 0,
        string $notes = '',
    ): CreateDnsRecordData {
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

        return CreateDnsRecordData::fromArray($response);
    }

    public function createFromBuilder(DnsRecordBuilder $dnsRecordBuilder): CreateDnsRecordData
    {
        $data = $dnsRecordBuilder->getData();

        $response = $this->post("/dns/create/{$this->domain}", $data);

        return CreateDnsRecordData::fromArray($response);
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

    /**
     * Updates ALL matching records by name and type.
     *
     * @param array<string, mixed> $data
     */
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

    public function retrieve(?int $id = null): DnsRecordCollection
    {
        $endpoint = "/dns/retrieve/{$this->domain}";
        if ($id !== null) {
            $endpoint .= "/{$id}";
        }

        $response = $this->post($endpoint);

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        return DnsRecordCollection::fromArray($records);
    }

    public function retrieveByType(string $type, ?string $name = null): DnsRecordCollection
    {
        $endpoint = "/dns/retrieveByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $response = $this->post($endpoint);

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        return DnsRecordCollection::fromArray($records);
    }

    /** @param array{keyTag: string, alg: string, digestType: string, digest: string, keyDataFlags?: string, keyDataProtocol?: string, keyDataAlgo?: string, keyDataPubKey?: string, maxSigLife?: string} $params */
    public function createDnssec(array $params): void
    {
        $this->post("/dns/createDnssecRecord/{$this->domain}", $params);
    }

    /** @return array<DnssecRecord> */
    public function getDnssecRecords(): array
    {
        $response = $this->post("/dns/getDnssecRecords/{$this->domain}");

        /** @var array<array<string, mixed>> $responseRecords */
        $responseRecords = $response['records'] ?? [];

        $records = [];
        foreach ($responseRecords as $responseRecord) {
            $records[] = DnssecRecord::fromArray($responseRecord);
        }

        return $records;
    }

    public function deleteDnssec(string $keyTag): void
    {
        $this->post("/dns/deleteDnssecRecord/{$this->domain}/{$keyTag}");
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
