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
        private string $notes = ''
    ) {
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function type(string|DnsRecordType $type): self
    {
        if (is_string($type)) {
            $enumValue = DnsRecordType::tryFrom(mb_strtoupper($type));
            if (!$enumValue instanceof DnsRecordType) {
                throw new InvalidArgumentException("Invalid record type: {$type}");
            }
            $this->dnsRecordType = $enumValue;
        } else {
            $this->dnsRecordType = $type;
        }

        // Re-validate existing content against the new type
        if ($this->content !== null && !$this->dnsRecordType->validateContent($this->content)) {
            throw new InvalidArgumentException("Invalid content for {$this->dnsRecordType->value} record: {$this->content}");
        }

        return $this;
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

        $this->content = $content;

        return $this;
    }

    public function ttl(int $ttl): self
    {
        if ($ttl < 1) {
            throw new InvalidArgumentException('TTL must be greater than 0');
        }

        $this->ttl = $ttl;

        return $this;
    }

    public function priority(int $priority): self
    {
        if ($priority < 0) {
            throw new InvalidArgumentException('Priority cannot be negative');
        }

        $this->prio = $priority;

        return $this;
    }

    public function notes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
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

        if ($this->notes !== '') {
            $data['notes'] = $this->notes;
        }

        return $data;
    }

    public function reset(): self
    {
        $this->name = '';
        $this->dnsRecordType = null;
        $this->content = null;
        $this->ttl = 600;
        $this->prio = 0;
        $this->notes = '';

        return $this;
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
}
