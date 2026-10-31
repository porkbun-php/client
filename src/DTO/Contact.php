<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class Contact implements JsonSerializable
{
    public function __construct(
        public string $firstName,
        public string $address1,
        public string $city,
        public string $postalCode,
        public string $country,
        public string $phone,
        public string $phoneCountryCode,
        public string $email,
        public string $lastName = '',
        public string $organization = '',
        public string $address2 = '',
        public string $address3 = '',
        public string $state = '',
        public string $fax = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) ($data['firstName'] ?? ''),
            address1: (string) ($data['address1'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            postalCode: (string) ($data['postalCode'] ?? ''),
            country: (string) ($data['country'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            phoneCountryCode: (string) ($data['phoneCountryCode'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            lastName: (string) ($data['lastName'] ?? ''),
            organization: (string) ($data['organization'] ?? ''),
            address2: (string) ($data['address2'] ?? ''),
            address3: (string) ($data['address3'] ?? ''),
            state: (string) ($data['state'] ?? ''),
            fax: (string) ($data['fax'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'organization' => $this->organization,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'address3' => $this->address3,
            'city' => $this->city,
            'state' => $this->state,
            'postalCode' => $this->postalCode,
            'country' => $this->country,
            'phone' => $this->phone,
            'phoneCountryCode' => $this->phoneCountryCode,
            'fax' => $this->fax,
            'email' => $this->email,
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
