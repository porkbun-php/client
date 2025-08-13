<?php

declare(strict_types=1);

namespace Porkbun\Response;

class DnsRecordsResponse extends AbstractResponse
{
    public function getRecords(): array
    {
        return $this->rawData['records'] ?? [];
    }

    public function hasRecords(): bool
    {
        $records = $this->rawData['records'] ?? [];

        return $records !== [];
    }

    public function getRecordCount(): int
    {
        return count($this->rawData['records'] ?? []);
    }

    public function getRecordById(int|string $id): ?array
    {
        $targetId = (int) $id;

        foreach ($this->getRecords() as $record) {
            if ((int) ($record['id'] ?? 0) === $targetId) {
                return $record;
            }
        }

        return null;
    }

    public function getRecordsByType(string $type): array
    {
        return array_filter($this->getRecords(), fn ($record): bool => ($record['type'] ?? '') === strtoupper($type));
    }

    public function getRecordsByName(string $name): array
    {
        return array_filter($this->getRecords(), fn ($record): bool => ($record['name'] ?? '') === $name);
    }
}
