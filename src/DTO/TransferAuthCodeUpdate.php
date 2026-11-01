<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final readonly class TransferAuthCodeUpdate implements JsonSerializable
{
    public function __construct(
        public string $domain,
        public ?bool $authCodeValid = null,
        public ?string $previousStatus = null,
        public ?string $transferStatus = null,
        public ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            domain: (string) ($data['domain'] ?? ''),
            authCodeValid: isset($data['authCodeValid']) ? TypeCaster::toBool($data['authCodeValid']) : null,
            previousStatus: isset($data['previousStatus']) ? (string) $data['previousStatus'] : null,
            transferStatus: isset($data['transferStatus']) ? (string) $data['transferStatus'] : null,
            message: isset($data['message']) ? (string) $data['message'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'domain' => $this->domain,
            'authCodeValid' => $this->authCodeValid,
            'previousStatus' => $this->previousStatus,
            'transferStatus' => $this->transferStatus,
            'message' => $this->message,
        ], fn (mixed $value): bool => $value !== null);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
