# Laravel MCP Fetch

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eloquage/laravel-mcp-fetch.svg?style=flat-square)](https://packagist.org/packages/eloquage/laravel-mcp-fetch)
[![GitHub Tests Action Status](https://github.com/eloquage/laravel-mcp-fetch/actions/workflows/run-tests.yml/badge.svg)](https://github.com/eloquage/laravel-mcp-fetch/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eloquage/laravel-mcp-fetch.svg?style=flat-square)](https://packagist.org/packages/eloquage/laravel-mcp-fetch)

MCP fetch server matching the [official MCP fetch tool schema](https://github.com/modelcontextprotocol/servers/tree/main/src/fetch). Fetches public URLs with SSRF protection and optional HTML simplification.

## Requirements

- PHP 8.4+
- `laravel/mcp` ^0.8

## Installation

```bash
composer require eloquage/laravel-mcp-fetch
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=laravel-mcp-fetch-config
```

## Registration

This package does **not** auto-register MCP routes. Wire the server in your app's `routes/ai.php`:

```php
use Eloquage\LaravelMcpFetch\Mcp\Servers\FetchServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('fetch', FetchServer::class);
```

Start the local MCP server:

```bash
php artisan mcp:start fetch
```

### IDE config (`.cursor/mcp.json`)

```json
{
    "mcpServers": {
        "fetch": {
            "command": "php",
            "args": ["artisan", "mcp:start", "fetch"]
        }
    }
}
```

## Tool: `fetch`

| Parameter     | Type         | Required | Default | Description                                         |
| ------------- | ------------ | -------- | ------- | --------------------------------------------------- |
| `url`         | string (uri) | yes      | —       | URL to fetch                                        |
| `max_length`  | integer      | no       | 5000    | Maximum characters to return (max 1,000,000)        |
| `start_index` | integer      | no       | 0       | Start output at this character index for pagination |
| `raw`         | boolean      | no       | false   | Return raw HTML without simplification              |

### Success response

```json
{
    "success": true,
    "url": "https://example.com/page",
    "content": "...",
    "truncated": false,
    "start_index": 0,
    "content_length": 1234,
    "total_length": 1234
}
```

### Error response

```json
{
    "success": false,
    "error": "HTTP 404 fetching https://example.com/missing"
}
```

## SSRF protection

Only public `http` and `https` URLs are allowed. Private IPs, localhost, and link-local addresses are blocked before any HTTP request is made.

## Programmatic usage

```php
use Eloquage\LaravelMcpFetch\Services\UrlFetchService;

$result = app(UrlFetchService::class)->fetch('https://example.com', maxLength: 5000);
```

## Configuration

| Key                  | Env                    | Default                      |
| -------------------- | ---------------------- | ---------------------------- |
| `timeout`            | `MCP_FETCH_TIMEOUT`    | 30                           |
| `user_agent`         | `MCP_FETCH_USER_AGENT` | `eloquage/laravel-mcp-fetch` |
| `default_max_length` | —                      | 5000                         |
| `max_max_length`     | —                      | 1000000                      |

## Testing

```bash
composer test
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
