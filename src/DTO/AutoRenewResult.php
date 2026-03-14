<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class AutoRenewResult implements JsonSerializable
{
    public bool $isFailure { get => !$this->success; }

    public function __construct(
        public readonly string $domain,
        public readonly bool $success,
        public readonly ?string $message = null,
    ) {
    }

    /**
     * @param array<string, array{status: string, message?: string}> $results
     *
     * @return list<self>
     */
    public static function fromResults(array $results): array
    {
        $items = [];
        foreach ($results as $domain => $result) {
            $items[] = new self(
                domain: $domain,
                success: ($result['status'] ?? '') === 'SUCCESS',
                message: $result['message'] ?? null,
            );
        }

        return $items;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            domain: (string) ($data['domain'] ?? ''),
            success: (bool) ($data['success'] ?? false),
            message: isset($data['message']) ? (string) $data['message'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'domain' => $this->domain,
            'success' => $this->success,
        ];

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
