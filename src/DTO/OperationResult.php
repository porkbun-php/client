<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class OperationResult implements JsonSerializable
{
    public function __construct(
        public ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            message: isset($data['message']) ? (string) $data['message'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
