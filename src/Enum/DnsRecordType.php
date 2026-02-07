<?php

declare(strict_types=1);

namespace Porkbun\Enum;

enum DnsRecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case MX = 'MX';
    case TXT = 'TXT';
    case NS = 'NS';
    case SRV = 'SRV';
    case TLSA = 'TLSA';
    case CAA = 'CAA';
    case HTTPS = 'HTTPS';
    case SVCB = 'SVCB';

    public function requiresPriority(): bool
    {
        return match ($this) {
            self::MX, self::SRV => true,
            default => false,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::A => 'IPv4 address record',
            self::AAAA => 'IPv6 address record',
            self::CNAME => 'Canonical name record',
            self::MX => 'Mail exchange record',
            self::TXT => 'Text record',
            self::NS => 'Name server record',
            self::SRV => 'Service locator record',
            self::TLSA => 'DANE DNS-based Authentication of Named Entities record',
            self::CAA => 'Certification Authority Authorization record',
            self::HTTPS => 'HTTPS service binding record',
            self::SVCB => 'Service binding record',
        };
    }

    public function validateContent(string $content): bool
    {
        return match ($this) {
            self::A => filter_var($content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            self::AAAA => filter_var($content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            self::CNAME, self::NS => $this->isValidDomainName($content),
            self::MX => $this->isValidDomainName($content),
            self::TXT => true, // TXT records can contain any text
            self::SRV => $this->isValidSrvRecord($content),
            self::TLSA => $this->isValidTlsaRecord($content),
            self::CAA => $this->isValidCaaRecord($content),
            self::HTTPS, self::SVCB => true, // Complex validation would require specific parsing
        };
    }

    private function isValidDomainName(string $domain): bool
    {
        return preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*\.?$/', $domain) === 1;
    }

    private function isValidSrvRecord(string $content): bool
    {
        // SRV format: priority weight port target
        return preg_match('/^\d+ \d+ \d+ .+$/', $content) === 1;
    }

    private function isValidTlsaRecord(string $content): bool
    {
        // TLSA format: usage selector matching-type certificate-association-data
        return preg_match('/^\d+ \d+ \d+ [a-fA-F0-9]+$/', $content) === 1;
    }

    private function isValidCaaRecord(string $content): bool
    {
        // CAA format: flags tag value
        return preg_match('/^\d+ [a-zA-Z]+ .+$/', $content) === 1;
    }
}
