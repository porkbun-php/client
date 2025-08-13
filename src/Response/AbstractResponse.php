<?php

declare(strict_types=1);

namespace Porkbun\Response;

abstract class AbstractResponse
{
    public function __construct(protected array $rawData)
    {
    }

    public function isSuccess(): bool
    {
        return ($this->rawData['status'] ?? '') === 'SUCCESS';
    }

    public function getStatus(): string
    {
        return $this->rawData['status'] ?? '';
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getMessage(): ?string
    {
        return $this->rawData['message'] ?? null;
    }
}
