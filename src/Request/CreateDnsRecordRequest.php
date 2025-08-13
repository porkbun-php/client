<?php

declare(strict_types=1);

namespace Porkbun\Request;

class CreateDnsRecordRequest extends AbstractRequest
{
    public function __construct(
        private string $domain,
        private string $name,
        private string $type,
        private string $content,
        private int $ttl = 600,
        private int $priority = 0,
        private string $notes = ''
    ) {
    }

    public function getEndpoint(): string
    {
        return "/dns/create/{$this->domain}";
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getData(): array
    {
        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'content' => $this->content,
            'ttl' => (string) $this->ttl,
            'prio' => (string) $this->priority,
        ];

        if ($this->notes !== '') {
            $data['notes'] = $this->notes;
        }

        return $data;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }
}
