<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Internal\ClientContext;

final class Dns extends AbstractApi
{
    public private(set) ?string $cloudflare = null;

    public private(set) bool $isCloudflareEnabled = false;

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

        $this->cloudflare = $response['cloudflare'] ?? null;
        $this->isCloudflareEnabled = $this->cloudflare === 'enabled';

        foreach ($records as &$record) {
            $record['name'] = $this->normalizeRecordName((string) ($record['name'] ?? ''));
        }

        return DnsRecordCollection::fromArray($records);
    }

    public function find(int $id): ?DnsRecord
    {
        $response = $this->post("/dns/retrieve/{$this->domain}/{$id}");

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        if ($records === []) {
            return null;
        }

        $records[0]['name'] = $this->normalizeRecordName((string) ($records[0]['name'] ?? ''));

        return DnsRecord::fromArray($records[0]);
    }

    public function findByType(string|DnsRecordType $type, ?string $name = null): DnsRecordCollection
    {
        $type = $type instanceof DnsRecordType ? $type->value : $type;
        $endpoint = "/dns/retrieveByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $response = $this->post($endpoint);

        /** @var array<array<string, mixed>> $records */
        $records = $response['records'] ?? [];

        $this->cloudflare = $response['cloudflare'] ?? null;
        $this->isCloudflareEnabled = $this->cloudflare === 'enabled';

        foreach ($records as &$record) {
            $record['name'] = $this->normalizeRecordName((string) ($record['name'] ?? ''));
        }

        return DnsRecordCollection::fromArray($records);
    }

    public function create(
        string $name,
        string|DnsRecordType $type,
        string $content,
        int|string $ttl = 600,
        int|string $prio = 0,
        string $notes = '',
    ): CreateResult {
        $data = [
            'name' => $name,
            'type' => $type instanceof DnsRecordType ? $type->value : $type,
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
        $data = $dnsRecordBuilder->data();

        $response = $this->post("/dns/create/{$this->domain}", $data);

        return CreateResult::fromArray($response);
    }

    public function record(): DnsRecordBuilder
    {
        return new DnsRecordBuilder();
    }

    public function update(
        int $id,
        string $name,
        string|DnsRecordType $type,
        string $content,
        int|string $ttl = 600,
        int|string $prio = 0,
        string $notes = '',
    ): void {
        $data = [
            'name' => $name,
            'type' => $type instanceof DnsRecordType ? $type->value : $type,
            'content' => $content,
            'ttl' => (string) $ttl,
            'prio' => (string) $prio,
        ];

        if ($notes !== '') {
            $data['notes'] = $notes;
        }

        $this->post("/dns/edit/{$this->domain}/{$id}", $data);
    }

    public function updateFromBuilder(int $id, DnsRecordBuilder $dnsRecordBuilder): void
    {
        $data = $dnsRecordBuilder->data();

        $this->post("/dns/edit/{$this->domain}/{$id}", $data);
    }

    /** Updates ALL matching records */
    public function updateByType(
        string|DnsRecordType $type,
        ?string $name,
        string $content,
        int|string $ttl = 600,
        int|string $prio = 0,
        string $notes = '',
    ): void {
        $type = $type instanceof DnsRecordType ? $type->value : $type;
        $endpoint = "/dns/editByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $data = [
            'content' => $content,
            'ttl' => (string) $ttl,
            'prio' => (string) $prio,
        ];

        if ($notes !== '') {
            $data['notes'] = $notes;
        }

        $this->post($endpoint, $data);
    }

    public function delete(int $id): void
    {
        $this->post("/dns/delete/{$this->domain}/{$id}");
    }

    public function deleteByType(string|DnsRecordType $type, ?string $name = null): void
    {
        $type = $type instanceof DnsRecordType ? $type->value : $type;
        $endpoint = "/dns/deleteByNameType/{$this->domain}/{$type}";
        if ($name !== null) {
            $endpoint .= "/{$name}";
        }

        $this->post($endpoint);
    }

    private function normalizeRecordName(string $name): string
    {
        $name = mb_strtolower(mb_rtrim($name, '.'));
        $domain = mb_strtolower(mb_rtrim($this->domain, '.'));

        if ($name === $domain) {
            return '';
        }

        $suffix = ".{$domain}";
        if (str_ends_with($name, $suffix)) {
            return mb_substr($name, 0, -mb_strlen($suffix));
        }

        return $name;
    }
}
