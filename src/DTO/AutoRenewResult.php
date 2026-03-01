<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class AutoRenewResult implements JsonSerializable
{
    public bool $isFailure;

    public function __construct(
        public string $domain,
        public bool $success,
        public ?string $message = null,
    ) {
        $this->isFailure = !$this->success;
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

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'domain' => $this->domain,
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
