<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class SslCertificate implements JsonSerializable
{
    public string $fullChain;

    public bool $hasCertificate;

    public bool $hasPrivateKey;

    public bool $hasIntermediateCertificate;

    public function __construct(
        public string $certificateChain,
        public string $privateKey,
        public string $publicKey,
        public ?string $intermediateCertificate = null,
    ) {
        $this->fullChain = ($this->intermediateCertificate !== null && $this->intermediateCertificate !== '')
            ? $this->certificateChain . "\n" . $this->intermediateCertificate
            : $this->certificateChain;
        $this->hasCertificate = $this->certificateChain !== '';
        $this->hasPrivateKey = $this->privateKey !== '';
        $this->hasIntermediateCertificate = $this->intermediateCertificate !== null && $this->intermediateCertificate !== '';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            certificateChain: (string) ($data['certificatechain'] ?? ''),
            privateKey: (string) ($data['privatekey'] ?? ''),
            publicKey: (string) ($data['publickey'] ?? ''),
            intermediateCertificate: isset($data['intermediatecertificate']) ? (string) $data['intermediatecertificate'] : null,
        );
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
