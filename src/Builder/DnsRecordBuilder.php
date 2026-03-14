<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

final class DnsRecordBuilder
{
    public function __construct(
        private string $name = '',
        private ?DnsRecordType $type = null,
        private ?string $content = null,
        private int $ttl = 600,
        private int $priority = 0,
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
        $clone->type = DnsRecordType::resolve($type);

        if ($clone->content !== null && !$clone->type->validateContent($clone->content)) {
            throw new InvalidArgumentException("Invalid content for {$clone->type->value} record: {$clone->content}");
        }

        return $clone;
    }

    public function content(string $content): self
    {
        if (mb_trim($content) === '') {
            throw new InvalidArgumentException('Content cannot be empty');
        }

        // Validate content based on record type if type is set
        if ($this->type instanceof DnsRecordType && !$this->type->validateContent($content)) {
            throw new InvalidArgumentException("Invalid content for {$this->type->value} record: {$content}");
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
        $clone->priority = $priority;

        return $clone;
    }

    public function notes(string $notes): self
    {
        $clone = clone $this;
        $clone->notes = $notes;

        return $clone;
    }

    /**
     * @internal Use createFromBuilder()/updateFromBuilder() instead of calling this directly.
     *
     * @return array<string, mixed>
     */
    public function toRequestData(): array
    {
        if (!$this->type instanceof DnsRecordType) {
            throw new InvalidArgumentException('Record type is required');
        }

        if ($this->content === null) {
            throw new InvalidArgumentException('Content is required');
        }

        $data = [
            'name' => $this->name,
            'type' => $this->type->value,
            'content' => $this->content,
            'ttl' => (string) $this->ttl,
            'prio' => (string) $this->priority,
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

    public function srv(string $value, int $priority = 0): self
    {
        return $this->type(DnsRecordType::SRV)->content($value)->priority($priority);
    }

    public function alias(string $target): self
    {
        return $this->type(DnsRecordType::ALIAS)->content($target);
    }

    public function sshfp(string $value): self
    {
        return $this->type(DnsRecordType::SSHFP)->content($value);
    }

    public function tlsa(string $value): self
    {
        return $this->type(DnsRecordType::TLSA)->content($value);
    }

    public function https(string $value): self
    {
        return $this->type(DnsRecordType::HTTPS)->content($value);
    }

    public function svcb(string $value): self
    {
        return $this->type(DnsRecordType::SVCB)->content($value);
    }
}
