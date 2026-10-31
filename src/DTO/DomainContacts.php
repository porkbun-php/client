<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class DomainContacts implements JsonSerializable
{
    public function __construct(
        public ?Contact $registrant = null,
        public ?Contact $admin = null,
        public ?Contact $tech = null,
        public ?Contact $billing = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            registrant: is_array($data['registrant'] ?? null) ? Contact::fromArray($data['registrant']) : null,
            admin: is_array($data['admin'] ?? null) ? Contact::fromArray($data['admin']) : null,
            tech: is_array($data['tech'] ?? null) ? Contact::fromArray($data['tech']) : null,
            billing: is_array($data['billing'] ?? null) ? Contact::fromArray($data['billing']) : null,
        );
    }

    public function toArray(): array
    {
        return array_map(
            fn (Contact $contact): array => $contact->toArray(),
            array_filter([
                'registrant' => $this->registrant,
                'admin' => $this->admin,
                'tech' => $this->tech,
                'billing' => $this->billing,
            ], static fn (?Contact $contact): bool => $contact instanceof Contact),
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
