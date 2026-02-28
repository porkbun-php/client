<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Porkbun\Api\Dns;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\PorkbunApiException;
use Porkbun\Internal\BatchOperation;
use Porkbun\Internal\BatchOperationType;

final class DnsBatchBuilder
{
    /** @var list<BatchOperation> */
    private array $operations = [];

    public function add(DnsRecordBuilder $dnsRecordBuilder): self
    {
        $this->operations[] = BatchOperation::create($dnsRecordBuilder->data());

        return $this;
    }

    public function addRecord(
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): self {
        $dnsRecordBuilder = new DnsRecordBuilder();
        $builder = $dnsRecordBuilder
            ->name($name)
            ->type($type)
            ->content($content)
            ->ttl($ttl)
            ->priority($priority);

        if ($notes !== null) {
            $builder = $builder->notes($notes);
        }

        $this->operations[] = BatchOperation::create($builder->data());

        return $this;
    }

    public function updateRecord(
        int $recordId,
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): self {
        $dnsRecordBuilder = new DnsRecordBuilder();
        $data = $dnsRecordBuilder
            ->name($name)
            ->type($type)
            ->content($content)
            ->ttl($ttl)
            ->priority($priority);

        if ($notes !== null) {
            $data = $data->notes($notes);
        }

        $this->operations[] = BatchOperation::update($recordId, $data->data());

        return $this;
    }

    public function deleteRecord(int $recordId): self
    {
        $this->operations[] = BatchOperation::delete($recordId);

        return $this;
    }

    public function deleteByNameType(string|DnsRecordType $type, ?string $subdomain = null): self
    {
        $this->operations[] = BatchOperation::deleteByNameType(
            DnsRecordType::resolve($type)->value,
            $subdomain,
        );

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
                    BatchOperationType::UPDATE => $this->executeUpdate($dns, $operation),
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

    public function operationsCount(): int
    {
        return count($this->operations);
    }

    private function executeCreate(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        /** @var array{name: string, type: string, content: string, ttl: int|string, prio: int|string, notes?: string} $data */
        $data = $batchOperation->data;

        $createResult = $dns->create(
            (string) $data['type'],
            (string) $data['name'],
            (string) $data['content'],
            (int) $data['ttl'],
            (int) $data['prio'],
            $data['notes'] ?? null,
        );

        return BatchOperationResult::success('create', recordId: $createResult->id);
    }

    private function executeUpdate(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        assert($batchOperation->id !== null);

        /** @var array{name: string, type: string, content: string, ttl: string, prio: string, notes?: string} $data */
        $data = $batchOperation->data;

        $dns->update(
            $batchOperation->id,
            $data['type'],
            $data['name'],
            $data['content'],
            (int) $data['ttl'],
            (int) $data['prio'],
            $data['notes'] ?? null,
        );

        return BatchOperationResult::success('update', recordId: $batchOperation->id);
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
