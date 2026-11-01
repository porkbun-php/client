<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final readonly class RegistrationRequirements implements JsonSerializable
{
    public function __construct(
        public string $tld,
        public bool $apiRegisterable,
        public int $registrationDurationYears,
        public bool $whoisPrivacySupported,
        public bool $requiresValidatedAddress,
        public bool $registrantOnly,
        /** @var array<string, mixed> */
        public array $requestSchema,
        public ?int $maxRegistrationYears = null,
        /** @var array<string, mixed>|null */
        public ?array $registryRequirements = null,
        public ?string $notApiRegisterableReason = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tld: (string) ($data['tld'] ?? ''),
            apiRegisterable: TypeCaster::toBool($data['apiRegisterable'] ?? false),
            registrationDurationYears: (int) ($data['registrationDurationYears'] ?? 1),
            whoisPrivacySupported: TypeCaster::toBool($data['whoisPrivacySupported'] ?? false),
            requiresValidatedAddress: TypeCaster::toBool($data['requiresValidatedAddress'] ?? false),
            registrantOnly: TypeCaster::toBool($data['registrantOnly'] ?? false),
            requestSchema: is_array($data['requestSchema'] ?? null) ? $data['requestSchema'] : [],
            maxRegistrationYears: isset($data['maxRegistrationYears']) ? (int) $data['maxRegistrationYears'] : null,
            registryRequirements: is_array($data['registryRequirements'] ?? null) ? $data['registryRequirements'] : null,
            notApiRegisterableReason: isset($data['notApiRegisterableReason'])
                ? (string) $data['notApiRegisterableReason']
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'tld' => $this->tld,
            'apiRegisterable' => $this->apiRegisterable,
            'registrationDurationYears' => $this->registrationDurationYears,
            'whoisPrivacySupported' => $this->whoisPrivacySupported,
            'requiresValidatedAddress' => $this->requiresValidatedAddress,
            'registrantOnly' => $this->registrantOnly,
            'requestSchema' => $this->requestSchema,
        ];

        if ($this->maxRegistrationYears !== null) {
            $data['maxRegistrationYears'] = $this->maxRegistrationYears;
        }

        if ($this->registryRequirements !== null) {
            $data['registryRequirements'] = $this->registryRequirements;
        }

        if ($this->notApiRegisterableReason !== null) {
            $data['notApiRegisterableReason'] = $this->notApiRegisterableReason;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
