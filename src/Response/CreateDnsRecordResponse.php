<?php

declare(strict_types=1);

namespace Porkbun\Response;

class CreateDnsRecordResponse extends AbstractResponse
{
    public function getId(): int
    {
        return (int) ($this->rawData['id'] ?? 0);
    }

    public function hasId(): bool
    {
        return isset($this->rawData['id']) && $this->rawData['id'] !== '';
    }
}
