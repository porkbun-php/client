# Changelog

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-03-01

### Added

- Complete Porkbun API v3 coverage (27/27 endpoints)
- Domain-centric design: `$client->domain('example.com')->dns()->all()`
- Typed immutable DTOs for all API responses
- Fluent DNS record builder with validation and convenience methods
- Batch DNS operations via `DnsBatchBuilder`
- Backed enums (`DnsRecordType`, `Endpoint`) with behavior methods
- Structured exception hierarchy (`ApiException`, `AuthenticationException`, `NetworkException`)
- Collection classes with filtering, iteration, and convenience methods
- DNS: full CRUD plus `findByType()`, `updateByType()`, `deleteByType()`
- DNSSEC record management
- SSL certificate retrieval
- Nameserver management
- URL forwarding management
- Glue record management
- Domain availability check and registration
- Auto-renewal management
- Domain listing with pagination and `allPages()` generator
- IPv4-only endpoint support
- Runtime credential switching and `clearAuth()`
- Laravel integration: deferred service provider, facade, config publishing
- PSR-18 HTTP client auto-discovery
