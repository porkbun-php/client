<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Exception;
use InvalidArgumentException;
use Porkbun\Service\DnsService;

class DnsBatchBuilder
{
    private array $operations = [];

    public function __construct(private DnsService $dnsService)
    {
    }

    public function addRecord(string $name, string $type, string $content, int $ttl = 600, int $priority = 0, string $notes = ''): self
    {
        $dnsRecordBuilder = new DnsRecordBuilder();
        $data = $dnsRecordBuilder
            ->name($name)
            ->type($type)
            ->content($content)
            ->ttl($ttl)
            ->priority($priority)
            ->notes($notes)
            ->getData();

        $this->operations[] = ['type' => 'create', 'data' => $data];

        return $this;
    }

    public function editRecord(int $recordId, array $data): self
    {
        $this->operations[] = ['type' => 'edit', 'id' => $recordId, 'data' => $data];

        return $this;
    }

    public function deleteRecord(int $recordId): self
    {
        $this->operations[] = ['type' => 'delete', 'id' => $recordId];

        return $this;
    }

    public function deleteByNameType(string $type, ?string $subdomain = null): self
    {
        $this->operations[] = ['type' => 'deleteByNameType', 'recordType' => $type, 'subdomain' => $subdomain];

        return $this;
    }

    public function commit(): array
    {
        $results = [];

        foreach ($this->operations as $operation) {
            try {
                $results[] = match ($operation['type']) {
                    'create' => [
                        'status' => 'success',
                        'operation' => 'create',
                        'id' => $this->dnsService->create(
                            $operation['data']['name'],
                            $operation['data']['type'],
                            $operation['data']['content'],
                            (int) $operation['data']['ttl'],
                            (int) $operation['data']['prio'],
                            $operation['data']['notes'] ?? ''
                        ),
                    ],
                    'edit' => (function () use ($operation): array {
                        $this->dnsService->edit($operation['id'], $operation['data']);

                        return ['status' => 'success', 'operation' => 'edit', 'id' => $operation['id']];
                    })(),
                    'delete' => (function () use ($operation): array {
                        $this->dnsService->delete($operation['id']);

                        return ['status' => 'success', 'operation' => 'delete', 'id' => $operation['id']];
                    })(),
                    'deleteByNameType' => (function () use ($operation): array {
                        $this->dnsService->deleteByNameType($operation['recordType'], $operation['subdomain']);

                        return ['status' => 'success', 'operation' => 'deleteByNameType', 'type' => $operation['recordType']];
                    })(),
                    default => throw new InvalidArgumentException("Unknown operation type: {$operation['type']}")
                };
            } catch (Exception $e) {
                $results[] = ['status' => 'error', 'operation' => $operation['type'], 'error' => $e->getMessage()];
            }
        }

        $this->operations = []; // Clear operations after commit

        return $results;
    }

    public function rollback(): self
    {
        $this->operations = [];

        return $this;
    }

    public function getOperationsCount(): int
    {
        return count($this->operations);
    }
}
