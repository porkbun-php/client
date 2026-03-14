<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\OperationResult;
use Porkbun\Enum\DnsRecordType;
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

        foreach ($records as &$record) {
            $record['name'] = self::stripDomainSuffix((string) ($record['name'] ?? ''), $this->domain);
        }

        return DnsRecordCollection::fromArray([
            'records' => $records,
            'cloudflare' => $response['cloudflare'] ?? null,
        ]);
    }

    public function find(int $id): ?DnsRecord
    {
        $response = $this->post("/dns/retrieve/{$this->domain}/{$id}");

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        if ($records === []) {
            return null;
        }

        $records[0]['name'] = self::stripDomainSuffix((string) ($records[0]['name'] ?? ''), $this->domain);

        return DnsRecord::fromArray($records[0]);
    }

    public function findByType(string|DnsRecordType $type, ?string $name = null): DnsRecordCollection
    {
        $resolvedType = DnsRecordType::resolve($type)->value;
        $endpoint = "/dns/retrieveByNameType/{$this->domain}/{$resolvedType}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $response = $this->post($endpoint);

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        foreach ($records as &$record) {
            $record['name'] = self::stripDomainSuffix((string) ($record['name'] ?? ''), $this->domain);
        }

        return DnsRecordCollection::fromArray([
            'records' => $records,
            'cloudflare' => $response['cloudflare'] ?? null,
        ]);
    }

    public function create(
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): CreateResult {
        $response = $this->post(
            "/dns/create/{$this->domain}",
            self::buildRecordData($type, $name, $content, $ttl, $priority, $notes),
        );

        return CreateResult::fromArray($response);
    }

    public function createFromBuilder(DnsRecordBuilder $dnsRecordBuilder): CreateResult
    {
        $data = $dnsRecordBuilder->toRequestData();

        $response = $this->post("/dns/create/{$this->domain}", $data);

        return CreateResult::fromArray($response);
    }

    public function record(): DnsRecordBuilder
    {
        return new DnsRecordBuilder();
    }

    public function batch(): DnsBatchBuilder
    {
        return new DnsBatchBuilder($this);
    }

    public function update(
        int $id,
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): OperationResult {
        $response = $this->post(
            "/dns/edit/{$this->domain}/{$id}",
            self::buildRecordData($type, $name, $content, $ttl, $priority, $notes),
        );

        return OperationResult::fromArray($response);
    }

    public function updateFromBuilder(int $id, DnsRecordBuilder $dnsRecordBuilder): OperationResult
    {
        $data = $dnsRecordBuilder->toRequestData();

        $response = $this->post("/dns/edit/{$this->domain}/{$id}", $data);

        return OperationResult::fromArray($response);
    }

    /** Updates ALL matching records. Pass null for $name to update all records of the given type. */
    public function updateByType(
        string|DnsRecordType $type,
        ?string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): OperationResult {

        $resolvedType = DnsRecordType::resolve($type)->value;
        $endpoint = "/dns/editByNameType/{$this->domain}/{$resolvedType}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $data = [
            'content' => $content,
            'ttl' => (string) $ttl,
            'prio' => (string) $priority,
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        $response = $this->post($endpoint, $data);

        return OperationResult::fromArray($response);
    }

    public function delete(int $id): OperationResult
    {
        $response = $this->post("/dns/delete/{$this->domain}/{$id}");

        return OperationResult::fromArray($response);
    }

    /** Deletes ALL matching records. Pass null for $name to delete all records of the given type. */
    public function deleteByType(string|DnsRecordType $type, ?string $name = null): OperationResult
    {
        $resolvedType = DnsRecordType::resolve($type)->value;
        $endpoint = "/dns/deleteByNameType/{$this->domain}/{$resolvedType}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $response = $this->post($endpoint);

        return OperationResult::fromArray($response);
    }

    /** @return array<string, string> */
    private static function buildRecordData(
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl,
        int $priority,
        ?string $notes,
    ): array {
        $data = [
            'name' => $name,
            'type' => DnsRecordType::resolve($type)->value,
            'content' => $content,
            'ttl' => (string) $ttl,
            'prio' => (string) $priority,
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        return $data;
    }
}
