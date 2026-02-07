<?php

declare(strict_types=1);

namespace Porkbun\DTO;

final readonly class BatchOperationResult
{
    public function __construct(
        public string $operation,
        public bool $success,
        public ?int $recordId = null,
        public ?string $recordType = null,
        public ?string $error = null
    ) {
    }

    public static function success(string $operation, ?int $recordId = null, ?string $recordType = null): self
    {
        return new self(
            operation: $operation,
            success: true,
            recordId: $recordId,
            recordType: $recordType
        );
    }

    public static function failure(string $operation, string $error): self
    {
        return new self(
            operation: $operation,
            success: false,
            error: $error
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function hasRecordId(): bool
    {
        return $this->recordId !== null;
    }

    public function toArray(): array
    {
        $result = [
            'operation' => $this->operation,
            'status' => $this->success ? 'success' : 'error',
        ];

        if ($this->recordId !== null) {
            $result['id'] = $this->recordId;
        }

        if ($this->recordType !== null) {
            $result['type'] = $this->recordType;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }
}
