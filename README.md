# Laravel MCP Fetch

![Laravel MCP Fetch](art/banner.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eloquage/laravel-mcp-fetch.svg?style=flat-square)](https://packagist.org/packages/eloquage/laravel-mcp-fetch)
[![GitHub Tests Action Status](https://github.com/eloquage/laravel-mcp-fetch/actions/workflows/run-tests.yml/badge.svg)](https://github.com/eloquage/laravel-mcp-fetch/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eloquage/laravel-mcp-fetch.svg?style=flat-square)](https://packagist.org/packages/eloquage/laravel-mcp-fetch)

MCP fetch server matching the [official MCP fetch tool schema](https://github.com/modelcontextprotocol/servers/tree/main/src/fetch). Fetches public URLs with SSRF protection and optional HTML simplification.

## Requirements

- PHP 8.4+
- `laravel/mcp` ^0.9

## Installation

```bash
composer require eloquage/laravel-mcp-fetch
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=mcp-fetch-config
```

## Run the MCP server

Laravel package discovery registers the MCP server automatically:

- The default local handle is `fetch`. Set `MCP_FETCH_LOCAL_HANDLE` to change it.

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

## Fetch a URL

The `fetch` tool accepts a public HTTP or HTTPS URL. For example, a client can call it with:

```json
{
  "url": "https://example.com/article",
  "max_length": 5000
}
```

## Tool reference: `fetch`

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

## Use the service in Laravel

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

## Docker

Published to [GHCR](https://github.com/eloquage/laravel-mcp-fetch/pkgs/container/laravel-mcp-fetch) on tag push.

```json
{
    "mcpServers": {
        "fetch": {
            "command": "docker",
            "args": ["run", "-i", "--rm", "ghcr.io/eloquage/laravel-mcp-fetch:latest"]
        }
    }
}
```

Local build:

```bash
docker build -t mcp-fetch:local --build-arg PACKAGE_VERSION=dev .
docker run --rm -i mcp-fetch:local
```

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
