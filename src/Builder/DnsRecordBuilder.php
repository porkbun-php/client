<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

final class DnsRecordBuilder
{
    public function __construct(
        private string $name = '',
        private ?DnsRecordType $dnsRecordType = null,
        private ?string $content = null,
        private int $ttl = 600,
        private int $prio = 0,
        private ?string $notes = null,
    ) {
    }

    public function name(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function type(string|DnsRecordType $type): self
    {
        $clone = clone $this;

        if (is_string($type)) {
            $enumValue = DnsRecordType::tryFrom(mb_strtoupper($type));
            if (!$enumValue instanceof DnsRecordType) {
                throw new InvalidArgumentException("Invalid record type: {$type}");
            }
            $clone->dnsRecordType = $enumValue;
        } else {
            $clone->dnsRecordType = $type;
        }

        // Re-validate existing content against the new type
        if ($clone->content !== null && !$clone->dnsRecordType->validateContent($clone->content)) {
            throw new InvalidArgumentException("Invalid content for {$clone->dnsRecordType->value} record: {$clone->content}");
        }

        return $clone;
    }

    public function content(string $content): self
    {
        if (mb_trim($content) === '') {
            throw new InvalidArgumentException('Content cannot be empty');
        }

        // Validate content based on record type if type is set
        if ($this->dnsRecordType instanceof DnsRecordType && !$this->dnsRecordType->validateContent($content)) {
            throw new InvalidArgumentException("Invalid content for {$this->dnsRecordType->value} record: {$content}");
        }

        $clone = clone $this;
        $clone->content = $content;

        return $clone;
    }

    public function ttl(int $ttl): self
    {
        if ($ttl < 1) {
            throw new InvalidArgumentException('TTL must be greater than 0');
        }

        $clone = clone $this;
        $clone->ttl = $ttl;

        return $clone;
    }

    public function priority(int $priority): self
    {
        if ($priority < 0) {
            throw new InvalidArgumentException('Priority cannot be negative');
        }

        $clone = clone $this;
        $clone->prio = $priority;

        return $clone;
    }

    public function notes(string $notes): self
    {
        $clone = clone $this;
        $clone->notes = $notes;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        if (!$this->dnsRecordType instanceof DnsRecordType) {
            throw new InvalidArgumentException('Record type is required');
        }

        if ($this->content === null) {
            throw new InvalidArgumentException('Content is required');
        }

        $data = [
            'name' => $this->name,
            'type' => $this->dnsRecordType->value,
            'content' => $this->content,
            'ttl' => (string) $this->ttl,
            'prio' => (string) $this->prio,
        ];

        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }

        return $data;
    }

    public function a(string $ipAddress): self
    {
        return $this->type(DnsRecordType::A)->content($ipAddress);
    }

    public function aaaa(string $ipv6Address): self
    {
        return $this->type(DnsRecordType::AAAA)->content($ipv6Address);
    }

    public function cname(string $target): self
    {
        return $this->type(DnsRecordType::CNAME)->content($target);
    }

    public function mx(string $mailServer, int $priority = 10): self
    {
        return $this->type(DnsRecordType::MX)->content($mailServer)->priority($priority);
    }

    public function txt(string $text): self
    {
        return $this->type(DnsRecordType::TXT)->content($text);
    }

    public function ns(string $nameserver): self
    {
        return $this->type(DnsRecordType::NS)->content($nameserver);
    }

    public function caa(string $value): self
    {
        return $this->type(DnsRecordType::CAA)->content($value);
    }

    public function srv(string $value): self
    {
        return $this->type(DnsRecordType::SRV)->content($value);
    }

    public function alias(string $target): self
    {
        return $this->type(DnsRecordType::ALIAS)->content($target);
    }

    public function sshfp(string $value): self
    {
        return $this->type(DnsRecordType::SSHFP)->content($value);
    }
}
