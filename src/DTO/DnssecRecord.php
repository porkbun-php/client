<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class DnssecRecord implements JsonSerializable
{
    public bool $isKsk { get => $this->flags !== null && ($this->flags & 257) === 257; }

    public bool $isZsk { get => $this->flags !== null && ($this->flags & 256) === 256 && !$this->isKsk; }

    public bool $isSecureEntryPoint { get => $this->flags !== null && ($this->flags & 1) === 1; }

    public bool $isModernAlgorithm {
        get => in_array($this->algorithm, [8, 10, 13, 14, 15, 16], true);
    }

    public bool $isModernDigestType {
        get => in_array($this->digestType, [2, 4], true);
    }

    /** @see https://www.iana.org/assignments/dns-sec-alg-numbers/dns-sec-alg-numbers.xhtml */
    public string $algorithmName {
        get => match ($this->algorithm) {
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

    public string $digestTypeName {
        get => match ($this->digestType) {
            0 => 'Reserved',
            1 => 'SHA-1',
            2 => 'SHA-256',
            3 => 'GOST R 34.11-94',
            4 => 'SHA-384',
            default => "Digest Type {$this->digestType}",
        };
    }

    public function __construct(
        public readonly int $keyTag,
        public readonly int $algorithm,
        public readonly int $digestType,
        public readonly string $digest,
        public readonly ?int $maxSigLife = null,
        public readonly ?int $flags = null,
        public readonly ?int $protocol = null,
        public readonly ?string $publicKey = null,
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
            'keyTag' => $this->keyTag,
            'algorithm' => $this->algorithm,
            'digestType' => $this->digestType,
            'digest' => $this->digest,
        ];

        if ($this->maxSigLife !== null) {
            $data['maxSigLife'] = $this->maxSigLife;
        }

        if ($this->flags !== null) {
            $data['flags'] = $this->flags;
        }

        if ($this->protocol !== null) {
            $data['protocol'] = $this->protocol;
        }

        if ($this->publicKey !== null) {
            $data['publicKey'] = $this->publicKey;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
