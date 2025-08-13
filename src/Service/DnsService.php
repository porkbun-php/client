<?php

declare(strict_types=1);

namespace Porkbun\Service;

use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\Config;
use Porkbun\Request\CreateDnsRecordRequest;
use Porkbun\Response\CreateDnsRecordResponse;
use Porkbun\Response\DnsRecordsResponse;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class DnsService extends AbstractService
{
    public function __construct(
        HttpClientInterface $httpClient,
        Config $config,
        private string $domain
    ) {
        parent::__construct($httpClient, $config);
    }

    public function create(string $name, string $type, string $content, int|string $ttl = 600, int|string $prio = 0, string $notes = ''): int
    {
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

        return (int) $response['id'];
    }

    public function edit(int $id, array $data): void
    {
        $this->post("/dns/edit/{$this->domain}/{$id}", $data);
    }

    public function editByNameType(string $type, ?string $subdomain, array $data): void
    {
        $endpoint = "/dns/editByNameType/{$this->domain}/{$type}";
        if ($subdomain !== null) {
            $endpoint .= "/{$subdomain}";
        }

        $this->post($endpoint, $data);
    }

    public function delete(int $id): void
    {
        $this->post("/dns/delete/{$this->domain}/{$id}");
    }

    public function deleteByNameType(string $type, ?string $subdomain): void
    {
        $endpoint = "/dns/deleteByNameType/{$this->domain}/{$type}";
        if ($subdomain !== null) {
            $endpoint .= "/{$subdomain}";
        }

        $this->post($endpoint);
    }

    public function retrieve(?int $id = null): array
    {
        $endpoint = "/dns/retrieve/{$this->domain}";
        if ($id !== null) {
            $endpoint .= "/{$id}";
        }

        return $this->post($endpoint);
    }

    public function retrieveByNameType(string $type, ?string $subdomain): array
    {
        $endpoint = "/dns/retrieveByNameType/{$this->domain}/{$type}";
        if ($subdomain !== null) {
            $endpoint .= "/{$subdomain}";
        }

        return $this->post($endpoint);
    }

    public function createDnssecRecord(array $params): void
    {
        $this->post("/dns/createDnssecRecord/{$this->domain}", $params);
    }

    public function getDnssecRecords(): array
    {
        return $this->post("/dns/getDnssecRecords/{$this->domain}");
    }

    public function deleteDnssecRecord(int $keyTag): void
    {
        $this->post("/dns/deleteDnssecRecord/{$this->domain}/{$keyTag}");
    }

    protected function requiresAuth(): bool
    {
        return true;
    }

    // Builder Pattern Methods

    public function record(): DnsRecordBuilder
    {
        return new DnsRecordBuilder();
    }

    public function createFromBuilder(DnsRecordBuilder $dnsRecordBuilder): int
    {
        $data = $dnsRecordBuilder->getData();

        return $this->create(
            $data['name'],
            $data['type'],
            $data['content'],
            (int) $data['ttl'],
            (int) $data['prio'],
            $data['notes'] ?? ''
        );
    }

    public function batch(): DnsBatchBuilder
    {
        return new DnsBatchBuilder($this);
    }

    // Request/Response Pattern Methods

    public function createFromRequest(CreateDnsRecordRequest $createDnsRecordRequest): CreateDnsRecordResponse
    {
        $response = $this->post($createDnsRecordRequest->getEndpoint(), $createDnsRecordRequest->getData());

        return new CreateDnsRecordResponse($response);
    }

    public function retrieveAsResponse(?int $id = null): DnsRecordsResponse
    {
        $response = $this->retrieve($id);

        return new DnsRecordsResponse($response);
    }
}
