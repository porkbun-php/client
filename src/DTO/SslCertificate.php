<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class SslCertificate implements JsonSerializable
{
    public string $fullChain {
        get => ($this->intermediateCertificate !== null && $this->intermediateCertificate !== '')
            ? $this->certificateChain . "\n" . $this->intermediateCertificate
            : $this->certificateChain;
    }

    public bool $hasCertificate { get => $this->certificateChain !== ''; }

    public bool $hasPrivateKey { get => $this->privateKey !== ''; }

    public bool $hasIntermediateCertificate { get => $this->intermediateCertificate !== null && $this->intermediateCertificate !== ''; }

    public function __construct(
        public readonly string $certificateChain,
        public readonly string $privateKey,
        public readonly string $publicKey,
        public readonly ?string $intermediateCertificate = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            certificateChain: (string) ($data['certificateChain'] ?? $data['certificatechain'] ?? ''),
            privateKey: (string) ($data['privateKey'] ?? $data['privatekey'] ?? ''),
            publicKey: (string) ($data['publicKey'] ?? $data['publickey'] ?? ''),
            intermediateCertificate: isset($data['intermediateCertificate']) || isset($data['intermediatecertificate'])
                ? (string) ($data['intermediateCertificate'] ?? $data['intermediatecertificate'])
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'certificateChain' => $this->certificateChain,
            'privateKey' => $this->privateKey,
            'publicKey' => $this->publicKey,
        ];

        if ($this->intermediateCertificate !== null) {
            $data['intermediateCertificate'] = $this->intermediateCertificate;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
