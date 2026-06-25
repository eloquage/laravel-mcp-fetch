# Laravel MCP Fetch

MCP fetch server matching the official fetch tool schema.

## Registration

Apps wire the server in `routes/ai.php` (not auto-registered by the package):

```php
use Eloquage\LaravelMcpFetch\Mcp\Servers\FetchServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('fetch', FetchServer::class);
```

Start via `php artisan mcp:start fetch`.

## Tool: fetch

| Param | Default | Notes |
|-------|---------|-------|
| `url` | required | Public http/https only (SSRF guarded) |
| `max_length` | 5000 | Max 1,000,000 |
| `start_index` | 0 | Use when previous response was truncated |
| `raw` | false | Skip HTML simplification |

## Service

`Eloquage\LaravelMcpFetch\Services\UrlFetchService::fetch()` — usable outside MCP.
