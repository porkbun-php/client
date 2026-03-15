# Changelog

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.1.0 - 2026-03-15

<!-- Release notes generated using configuration in .github/release.yml at v1.1.0 -->
### What's Changed

#### Added

* Wrap PSR-18 discovery failure with actionable error message by @glebovdev in https://github.com/porkbun-php/client/pull/11

#### Fixed

* Use PAT in release workflow to bypass branch protection by @glebovdev in https://github.com/porkbun-php/client/pull/12

### New Contributors

* @glebovdev made their first contribution in https://github.com/porkbun-php/client/pull/10

**Full Changelog**: https://github.com/porkbun-php/client/compare/v1.0.0...v1.1.0

## [Unreleased]

## [1.0.0] - 2026-03-14

### Added

- Complete Porkbun API v3 coverage (27/27 endpoints)
- Domain-centric design: `$client->domain('example.com')->dns()->all()`
- Typed immutable DTOs for all API responses
- Fluent DNS record builder with validation and convenience methods
- Batch DNS operations via `DnsBatchBuilder`
- Backed enums (`DnsRecordType`, `Endpoint`, `UrlForwardType`, `BatchOperationType`)
- Structured exception hierarchy (`ApiException`, `AuthenticationException`, `NetworkException`)
- Collection classes with filtering, iteration, and convenience methods
- DNS: full CRUD plus `findByType()`, `updateByType()`, `deleteByType()`
- DNSSEC record management
- SSL certificate retrieval
- Nameserver management
- URL forwarding management with typed forward types
- Glue record management
- Domain availability check and registration
- Auto-renewal management (single domain and bulk)
- Domain listing with pagination and `all()` generator
- IPv4-only endpoint support
- Runtime credential switching via `authenticate()` and `clearAuth()`
- Laravel integration: deferred service provider, facade, config publishing
- PSR-18 HTTP client auto-discovery
