<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final readonly class ContactsUpdateResult implements JsonSerializable
{
    public function __construct(
        public bool $registrantChanged,
        public ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            registrantChanged: TypeCaster::toBool($data['registrantChanged'] ?? false),
            message: isset($data['message']) ? (string) $data['message'] : null,
        );
    }

    public function toArray(): array
    {
        $data = ['registrantChanged' => $this->registrantChanged];

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
