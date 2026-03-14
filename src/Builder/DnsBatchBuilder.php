<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Countable;
use Override;
use Porkbun\Api\Dns;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\DTO\BatchResult;
use Porkbun\Enum\BatchOperationType;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\ExceptionInterface;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Internal\BatchOperation;

final class DnsBatchBuilder implements Countable
{
    /** @var list<BatchOperation> */
    private array $operations = [];

    public function __construct(
        private readonly ?Dns $dns = null,
    ) {
    }

    public function add(DnsRecordBuilder $dnsRecordBuilder): self
    {
        $clone = clone $this;
        $clone->operations[] = BatchOperation::create($dnsRecordBuilder->toRequestData());

        return $clone;
    }

    public function addRecord(
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): self {
        $clone = clone $this;
        $clone->operations[] = BatchOperation::create(
            self::buildRecord($type, $name, $content, $ttl, $priority, $notes),
        );

        return $clone;
    }

    public function updateRecord(
        int $id,
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl = 600,
        int $priority = 0,
        ?string $notes = null,
    ): self {
        $clone = clone $this;
        $clone->operations[] = BatchOperation::update(
            $id,
            self::buildRecord($type, $name, $content, $ttl, $priority, $notes),
        );

        return $clone;
    }

    public function deleteRecord(int $id): self
    {
        $clone = clone $this;
        $clone->operations[] = BatchOperation::delete($id);

        return $clone;
    }

    public function deleteByType(string|DnsRecordType $type, ?string $name = null): self
    {
        $clone = clone $this;
        $clone->operations[] = BatchOperation::deleteByNameType(
            DnsRecordType::resolve($type)->value,
            $name,
        );

        return $clone;
    }

    public function execute(?Dns $dns = null): BatchResult
    {
        $dns ??= $this->dns ?? throw new InvalidArgumentException(
            'No Dns instance provided. Use $dns->batch() or pass a Dns instance to execute().',
        );

        $results = [];

        foreach ($this->operations as $operation) {
            try {
                $results[] = match ($operation->type) {
                    BatchOperationType::CREATE => $this->executeCreate($dns, $operation),
                    BatchOperationType::UPDATE => $this->executeUpdate($dns, $operation),
                    BatchOperationType::DELETE => $this->executeDelete($dns, $operation),
                    BatchOperationType::DELETE_BY_NAME_TYPE => $this->executeDeleteByNameType($dns, $operation),
                };
            } catch (ExceptionInterface $e) {
                $results[] = BatchOperationResult::failure($operation->type, $e->getMessage());
            }
        }

        return new BatchResult($results);
    }

    public function clear(): self
    {
        return new self($this->dns);
    }

    #[Override]
    public function count(): int
    {
        return count($this->operations);
    }

    /** @return array<string, mixed> */
    private static function buildRecord(
        string|DnsRecordType $type,
        string $name,
        string $content,
        int $ttl,
        int $priority,
        ?string $notes,
    ): array {
        $builder = new DnsRecordBuilder()
            ->name($name)
            ->type($type)
            ->content($content)
            ->ttl($ttl)
            ->priority($priority);

        if ($notes !== null) {
            $builder = $builder->notes($notes);
        }

        return $builder->toRequestData();
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

        return BatchOperationResult::success(BatchOperationType::CREATE, recordId: $createResult->id, recordType: DnsRecordType::resolve((string) $data['type']));
    }

    private function executeUpdate(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        if ($batchOperation->id === null) {
            throw new InvalidArgumentException('Batch update operation requires a record ID.');
        }

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

        return BatchOperationResult::success(BatchOperationType::UPDATE, recordId: $batchOperation->id);
    }

    private function executeDelete(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        if ($batchOperation->id === null) {
            throw new InvalidArgumentException('Batch delete operation requires a record ID.');
        }

        $dns->delete($batchOperation->id);

        return BatchOperationResult::success(BatchOperationType::DELETE, recordId: $batchOperation->id);
    }

    private function executeDeleteByNameType(Dns $dns, BatchOperation $batchOperation): BatchOperationResult
    {
        if ($batchOperation->recordType === null) {
            throw new InvalidArgumentException('Batch deleteByNameType operation requires a record type.');
        }

        $dns->deleteByType($batchOperation->recordType, $batchOperation->name);

        return BatchOperationResult::success(BatchOperationType::DELETE_BY_NAME_TYPE, recordType: DnsRecordType::resolve($batchOperation->recordType));
    }
}
