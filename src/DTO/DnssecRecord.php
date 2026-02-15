<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class DnssecRecord implements JsonSerializable
{
    public function __construct(
        public int $keyTag,
        public int $algorithm,
        public int $digestType,
        public string $digest,
        public ?int $maxSigLife = null,
        public ?int $flags = null,
        public ?int $protocol = null,
        public ?string $publicKey = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            keyTag: (int) ($data['keyTag'] ?? 0),
            algorithm: (int) ($data['algorithm'] ?? $data['alg'] ?? 0),
            digestType: (int) ($data['digestType'] ?? 0),
            digest: (string) ($data['digest'] ?? ''),
            maxSigLife: isset($data['maxSigLife']) ? (int) $data['maxSigLife'] : null,
            flags: isset($data['flags']) ? (int) $data['flags'] : null,
            protocol: isset($data['protocol']) ? (int) $data['protocol'] : null,
            publicKey: $data['publicKey'] ?? $data['keyDataPubKey'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'keyTag' => (string) $this->keyTag,
            'alg' => (string) $this->algorithm,
            'digestType' => (string) $this->digestType,
            'digest' => $this->digest,
        ];

        if ($this->maxSigLife !== null) {
            $data['maxSigLife'] = (string) $this->maxSigLife;
        }

        if ($this->flags !== null) {
            $data['keyDataFlags'] = (string) $this->flags;
        }

        if ($this->protocol !== null) {
            $data['keyDataProtocol'] = (string) $this->protocol;
        }

        if ($this->publicKey !== null) {
            // Only include keyDataAlgo when other key data fields are present
            $data['keyDataAlgo'] = (string) $this->algorithm;
            $data['keyDataPubKey'] = $this->publicKey;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @see https://www.iana.org/assignments/dns-sec-alg-numbers/dns-sec-alg-numbers.xhtml */
    public function getAlgorithmName(): string
    {
        return match ($this->algorithm) {
            1 => 'RSAMD5',
            3 => 'DSA',
            5 => 'RSASHA1',
            6 => 'DSA-NSEC3-SHA1',
            7 => 'RSASHA1-NSEC3-SHA1',
            8 => 'RSASHA256',
            10 => 'RSASHA512',
            12 => 'ECC-GOST',
            13 => 'ECDSAP256SHA256',
            14 => 'ECDSAP384SHA384',
            15 => 'ED25519',
            16 => 'ED448',
            default => "Algorithm {$this->algorithm}",
        };
    }

    public function getDigestTypeName(): string
    {
        return match ($this->digestType) {
            0 => 'Reserved',
            1 => 'SHA-1',
            2 => 'SHA-256',
            3 => 'GOST R 34.11-94',
            4 => 'SHA-384',
            default => "Digest Type {$this->digestType}",
        };
    }

    public function isKsk(): bool
    {
        return $this->flags !== null && ($this->flags & 257) === 257;
    }

    public function isZsk(): bool
    {
        return $this->flags !== null && ($this->flags & 256) === 256 && !$this->isKsk();
    }

    public function isSecureEntryPoint(): bool
    {
        return $this->flags !== null && ($this->flags & 1) === 1;
    }

    public function isModernAlgorithm(): bool
    {
        return in_array($this->algorithm, [
            8,  // RSASHA256
            10, // RSASHA512
            13, // ECDSAP256SHA256
            14, // ECDSAP384SHA384
            15, // ED25519
            16, // ED448
        ], true);
    }

    public function isModernDigestType(): bool
    {
        return in_array($this->digestType, [
            2, // SHA-256
            4, // SHA-384
        ], true);
    }
}
