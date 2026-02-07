<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Porkbun\Api\Dns;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Exception\PorkbunApiException;

final class DnsBatchBuilder
{
    /** @var list<array<string, mixed>> */
    private array $operations = [];

    public function addRecord(
        string $name,
        string $type,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        string $notes = '',
    ): self {
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

    /**
     * @return array<BatchOperationResult>
     */
    public function execute(Dns $dns): array
    {
        $results = [];

        foreach ($this->operations as $operation) {
            /** @var string $type */
            $type = $operation['type'];

            try {
                $results[] = match ($type) {
                    'create' => $this->executeCreate($dns, $operation),
                    'edit' => $this->executeEdit($dns, $operation),
                    'delete' => $this->executeDelete($dns, $operation),
                    'deleteByNameType' => $this->executeDeleteByNameType($dns, $operation),
                    default => throw new InvalidArgumentException("Unknown operation type: {$type}")
                };
            } catch (PorkbunApiException $e) {
                $results[] = BatchOperationResult::failure($type, $e->getMessage());
            }
        }

        $this->operations = [];

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

    /**
     * @param array<string, mixed> $operation
     */
    private function executeCreate(Dns $dns, array $operation): BatchOperationResult
    {
        /** @var array{name: string, type: string, content: string, ttl: int|string, prio: int|string, notes?: string} $data */
        $data = $operation['data'];

        $createDnsRecordData = $dns->create(
            (string) $data['name'],
            (string) $data['type'],
            (string) $data['content'],
            (int) $data['ttl'],
            (int) $data['prio'],
            (string) ($data['notes'] ?? '')
        );

        return BatchOperationResult::success('create', recordId: $createDnsRecordData->id);
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function executeEdit(Dns $dns, array $operation): BatchOperationResult
    {
        /** @var int $id */
        $id = $operation['id'];
        /** @var array<string, mixed> $data */
        $data = $operation['data'];

        $dns->edit($id, $data);

        return BatchOperationResult::success('edit', recordId: $id);
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function executeDelete(Dns $dns, array $operation): BatchOperationResult
    {
        /** @var int $id */
        $id = $operation['id'];

        $dns->delete($id);

        return BatchOperationResult::success('delete', recordId: $id);
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function executeDeleteByNameType(Dns $dns, array $operation): BatchOperationResult
    {
        /** @var string $recordType */
        $recordType = $operation['recordType'];
        /** @var string|null $subdomain */
        $subdomain = $operation['subdomain'];

        $dns->deleteByType($recordType, $subdomain);

        return BatchOperationResult::success('deleteByNameType', recordType: $recordType);
    }
}
