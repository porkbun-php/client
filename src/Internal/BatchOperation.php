<?php

declare(strict_types=1);

namespace Porkbun\Internal;

/**
 * @internal
 */
final readonly class BatchOperation
{
    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        public BatchOperationType $type,
        public ?int $id,
        public array $data,
        public ?string $recordType,
        public ?string $subdomain,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            type: BatchOperationType::CREATE,
            id: null,
            data: $data,
            recordType: null,
            subdomain: null,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): self
    {
        return new self(
            type: BatchOperationType::UPDATE,
            id: $id,
            data: $data,
            recordType: null,
            subdomain: null,
        );
    }

    public static function delete(int $id): self
    {
        return new self(
            type: BatchOperationType::DELETE,
            id: $id,
            data: [],
            recordType: null,
            subdomain: null,
        );
    }

    public static function deleteByNameType(string $recordType, ?string $subdomain = null): self
    {
        return new self(
            type: BatchOperationType::DELETE_BY_NAME_TYPE,
            id: null,
            data: [],
            recordType: $recordType,
            subdomain: $subdomain,
        );
    }
}
