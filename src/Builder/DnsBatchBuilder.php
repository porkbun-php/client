<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Porkbun\Api\Dns;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\Exception\PorkbunApiException;
use Porkbun\Internal\BatchOperation;
use Porkbun\Internal\BatchOperationType;

final class DnsBatchBuilder
{
    /** @var list<BatchOperation> */
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

        $this->operations[] = BatchOperation::create($data);

        return $this;
    }

    public function editRecord(int $recordId, array $data): self
    {
        $this->operations[] = BatchOperation::edit($recordId, $data);

        return $this;
    }

    public function deleteRecord(int $recordId): self
    {
        $this->operations[] = BatchOperation::delete($recordId);

        return $this;
    }

    public function deleteByNameType(string $type, ?string $subdomain = null): self
    {
        $this->operations[] = BatchOperation::deleteByNameType($type, $subdomain);

        return $this;
    }

    /**
     * @return array<BatchOperationResult>
     */
    public function execute(Dns $dns): array
    {
        $results = [];

        foreach ($this->operations as $operation) {
            try {
                $results[] = match ($operation->type) {
                    BatchOperationType::CREATE => $this->executeCreate($dns, $operation),
                    BatchOperationType::EDIT => $this->executeEdit($dns, $operation),
                    BatchOperationType::DELETE => $this->executeDelete($dns, $operation),
                    BatchOperationType::DELETE_BY_NAME_TYPE => $this->executeDeleteByNameType($dns, $operation),
                };
            } catch (PorkbunApiException $e) {
                $results[] = BatchOperationResult::failure($operation->type->value, $e->getMessage());
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

    private function executeCreate(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        /** @var array{name: string, type: string, content: string, ttl: int|string, prio: int|string, notes?: string} $data */
        $data = $batchOperation->data;

        $createResult = $dns->create(
            (string) $data['name'],
            (string) $data['type'],
            (string) $data['content'],
            (int) $data['ttl'],
            (int) $data['prio'],
            (string) ($data['notes'] ?? '')
        );

        return BatchOperationResult::success('create', recordId: $createResult->id);
    }

    private function executeEdit(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        assert($batchOperation->id !== null);

        $dns->edit($batchOperation->id, $batchOperation->data);

        return BatchOperationResult::success('edit', recordId: $batchOperation->id);
    }

    private function executeDelete(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        assert($batchOperation->id !== null);

        $dns->delete($batchOperation->id);

        return BatchOperationResult::success('delete', recordId: $batchOperation->id);
    }

    private function executeDeleteByNameType(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        assert($batchOperation->recordType !== null);

        $dns->deleteByType($batchOperation->recordType, $batchOperation->subdomain);

        return BatchOperationResult::success('deleteByNameType', recordType: $batchOperation->recordType);
    }
}
