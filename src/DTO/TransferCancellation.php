<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final readonly class TransferCancellation implements JsonSerializable
{
    public function __construct(
        public string $domain,
        public ?bool $cancelled = null,
        public ?bool $withdrawnAtRegistry = null,
        public ?bool $refunded = null,
        public ?int $refundAmountInCents = null,
        public ?int $orderId = null,
        public ?string $previousStatus = null,
        public ?string $transferStatus = null,
        public ?string $registryResultCode = null,
        public ?string $registryCode = null,
        public ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $refundAmount = $data['refundAmountInCents'] ?? $data['refundAmount'] ?? null;

        return new self(
            domain: (string) ($data['domain'] ?? ''),
            cancelled: isset($data['cancelled']) ? TypeCaster::toBool($data['cancelled']) : null,
            withdrawnAtRegistry: isset($data['withdrawnAtRegistry']) ? TypeCaster::toBool($data['withdrawnAtRegistry']) : null,
            refunded: isset($data['refunded']) ? TypeCaster::toBool($data['refunded']) : null,
            refundAmountInCents: $refundAmount !== null ? (int) $refundAmount : null,
            orderId: isset($data['orderId']) ? (int) $data['orderId'] : null,
            previousStatus: isset($data['previousStatus']) ? (string) $data['previousStatus'] : null,
            transferStatus: isset($data['transferStatus']) ? (string) $data['transferStatus'] : null,
            registryResultCode: isset($data['registryResultCode']) ? (string) $data['registryResultCode'] : null,
            registryCode: isset($data['registryCode']) ? (string) $data['registryCode'] : null,
            message: isset($data['message']) ? (string) $data['message'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'domain' => $this->domain,
            'cancelled' => $this->cancelled,
            'withdrawnAtRegistry' => $this->withdrawnAtRegistry,
            'refunded' => $this->refunded,
            'refundAmountInCents' => $this->refundAmountInCents,
            'orderId' => $this->orderId,
            'previousStatus' => $this->previousStatus,
            'transferStatus' => $this->transferStatus,
            'registryResultCode' => $this->registryResultCode,
            'registryCode' => $this->registryCode,
            'message' => $this->message,
        ], fn (mixed $value): bool => $value !== null);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
