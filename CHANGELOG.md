# Changelog

All notable changes to `laravel-mcp-fetch` will be documented in this file.

## v1.0.3 - 2026-10-23

### Security and release readiness

- Pin outbound HTTP requests to the validated DNS answers to prevent DNS rebinding.
- Reject non-global IPv4 and IPv6 addresses and bypass proxies that could resolve hosts outside validation.
- Require the PHP cURL extension for DNS-pinned requests.

Validated with Pest, PHPStan, formatting, Composer validation and audit, plus Laravel 12 and Laravel 13 consumer tests.

## 1.0.0 - 2026-06-25

- Initial release
- MCP `fetch` server and tool matching the official fetch schema
- `UrlFetchService` with SSRF protection, HTML simplification, and pagination
- Ported from hardened the-truth implementation
