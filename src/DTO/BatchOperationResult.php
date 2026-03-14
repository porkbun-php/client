<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Enum\BatchOperationType;
use Porkbun\Enum\DnsRecordType;

final class BatchOperationResult implements JsonSerializable
{
    public bool $isFailure { get => !$this->success; }

    public bool $hasRecordId { get => $this->recordId !== null; }

    public function __construct(
        public readonly BatchOperationType $operation,
        public readonly bool $success,
        public readonly ?int $recordId = null,
        public readonly ?DnsRecordType $recordType = null,
        public readonly ?string $error = null
    ) {
    }

    public static function success(BatchOperationType $batchOperationType, ?int $recordId = null, ?DnsRecordType $recordType = null): self
    {
        return new self(
            operation: $batchOperationType,
            success: true,
            recordId: $recordId,
            recordType: $recordType
        );
    }

    public static function failure(BatchOperationType $batchOperationType, string $error): self
    {
        return new self(
            operation: $batchOperationType,
            success: false,
            error: $error
        );
    }

    public static function fromArray(array $data): self
    {
        $recordType = isset($data['type']) ? DnsRecordType::tryFrom(mb_strtoupper((string) $data['type'])) : null;

        return new self(
            operation: BatchOperationType::from(mb_strtolower((string) ($data['operation'] ?? ''))),
            success: mb_strtoupper((string) ($data['status'] ?? '')) === 'SUCCESS',
            recordId: isset($data['id']) ? (int) $data['id'] : null,
            recordType: $recordType,
            error: isset($data['error']) ? (string) $data['error'] : null,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $result = [
            'operation' => $this->operation->value,
            'status' => $this->success ? 'SUCCESS' : 'ERROR',
        ];

        if ($this->recordId !== null) {
            $result['id'] = $this->recordId;
        }

        if ($this->recordType !== null) {
            $result['type'] = $this->recordType->value;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }
}
