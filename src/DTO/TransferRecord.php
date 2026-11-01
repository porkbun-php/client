<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JsonSerializable;
use Override;

final readonly class TransferRecord implements JsonSerializable
{
    public function __construct(
        public string $domain,
        public string $status,
        public string $statusDescription,
        public int $orderId,
        public ?DateTimeImmutable $transferDate = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $transferDate = null;
        if (is_string($data['transferDate'] ?? null) && $data['transferDate'] !== '') {
            try {
                $transferDate = new DateTimeImmutable($data['transferDate'], new DateTimeZone('UTC'));
            } catch (Exception) {
            }
        }

        return new self(
            domain: (string) ($data['domain'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            statusDescription: (string) ($data['statusDescription'] ?? ''),
            orderId: (int) ($data['orderId'] ?? 0),
            transferDate: $transferDate,
        );
    }

    public function toArray(): array
    {
        $data = [
            'domain' => $this->domain,
            'status' => $this->status,
            'statusDescription' => $this->statusDescription,
            'orderId' => $this->orderId,
        ];

        if ($this->transferDate instanceof DateTimeImmutable) {
            $data['transferDate'] = $this->transferDate->format('c');
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
