<?php

declare(strict_types=1);

namespace Porkbun\Builder;

use Porkbun\Exception\InvalidArgumentException;

class DnsRecordBuilder
{
    private string $name = '';

    private ?string $type = null;

    private ?string $content = null;

    private int $ttl = 600;

    private int $prio = 0;

    private string $notes = '';

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function type(string $type): self
    {
        $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'TLSA', 'CAA', 'HTTPS', 'SVCB'];

        if (!in_array(strtoupper($type), $validTypes, true)) {
            throw new InvalidArgumentException("Invalid record type: {$type}");
        }

        $this->type = strtoupper($type);

        return $this;
    }

    public function content(string $content): self
    {
        if (trim($content) === '') {
            throw new InvalidArgumentException('Content cannot be empty');
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

    public function getData(): array
    {
        if ($this->type === null) {
            throw new InvalidArgumentException('Record type is required');
        }

        if ($this->content === null) {
            throw new InvalidArgumentException('Content is required');
        }

        $data = [
            'name' => $this->name,
            'type' => $this->type,
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
        $this->type = null;
        $this->content = null;
        $this->ttl = 600;
        $this->prio = 0;
        $this->notes = '';

        return $this;
    }

    // Convenience methods for common record types
    public function a(string $ipAddress): self
    {
        return $this->type('A')->content($ipAddress);
    }

    public function aaaa(string $ipv6Address): self
    {
        return $this->type('AAAA')->content($ipv6Address);
    }

    public function cname(string $target): self
    {
        return $this->type('CNAME')->content($target);
    }

    public function mx(string $mailServer, int $priority = 10): self
    {
        return $this->type('MX')->content($mailServer)->priority($priority);
    }

    public function txt(string $text): self
    {
        return $this->type('TXT')->content($text);
    }

    public function ns(string $nameserver): self
    {
        return $this->type('NS')->content($nameserver);
    }
}
