<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class DomainTransfer implements JsonSerializable
{
    public float $balanceInDollars { get => $this->balanceInCents / 100; }

    public bool $hasLimits { get => $this->limits !== null && $this->limits !== []; }

    public ?int $remainingAttempts {
        get {
            $limit = $this->limits['attempts'] ?? null;

            return $limit !== null
                ? max(0, (int) ($limit['limit'] ?? 0) - (int) ($limit['used'] ?? 0))
                : null;
        }
    }

    public ?int $remainingTransfers {
        get {
            $limit = $this->limits['success'] ?? null;

            return $limit !== null
                ? max(0, (int) ($limit['limit'] ?? 0) - (int) ($limit['used'] ?? 0))
                : null;
        }
    }

    public function __construct(
        public readonly string $domain,
        public readonly int $orderId,
        public readonly int $transferId,
        public readonly int $balanceInCents,
        public readonly ?string $message = null,
        /** @var ?array{attempts?: array{limit: int, used: int}, success?: array{limit: int, used: int}} */
        public readonly ?array $limits = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            domain: (string) ($data['domain'] ?? ''),
            orderId: (int) ($data['orderId'] ?? 0),
            transferId: (int) ($data['transferId'] ?? 0),
            balanceInCents: (int) ($data['balanceInCents'] ?? $data['balance'] ?? 0),
            message: isset($data['message']) ? (string) $data['message'] : null,
            limits: isset($data['limits']) && is_array($data['limits']) ? $data['limits'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'domain' => $this->domain,
            'orderId' => $this->orderId,
            'transferId' => $this->transferId,
            'balanceInCents' => $this->balanceInCents,
        ];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if ($this->limits !== null) {
            $data['limits'] = $this->limits;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
