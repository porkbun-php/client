<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class SslCertificate implements JsonSerializable
{
    public function __construct(
        public string $certificateChain,
        public string $privateKey,
        public string $publicKey,
        public ?string $intermediateCertificate = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            certificateChain: (string) ($data['certificatechain'] ?? ''),
            privateKey: (string) ($data['privatekey'] ?? ''),
            publicKey: (string) ($data['publickey'] ?? ''),
            intermediateCertificate: $data['intermediatecertificate'] ?? null,
        );
    }

    public function getFullChain(): string
    {
        if ($this->intermediateCertificate !== null && $this->intermediateCertificate !== '') {
            return $this->certificateChain . "\n" . $this->intermediateCertificate;
        }

        return $this->certificateChain;
    }

    public function hasCertificate(): bool
    {
        return $this->certificateChain !== '';
    }

    public function hasPrivateKey(): bool
    {
        return $this->privateKey !== '';
    }

    public function hasIntermediateCertificate(): bool
    {
        return $this->intermediateCertificate !== null && $this->intermediateCertificate !== '';
    }

    public function toArray(): array
    {
        $data = [
            'certificatechain' => $this->certificateChain,
            'privatekey' => $this->privateKey,
            'publickey' => $this->publicKey,
        ];

        if ($this->intermediateCertificate !== null) {
            $data['intermediatecertificate'] = $this->intermediateCertificate;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
