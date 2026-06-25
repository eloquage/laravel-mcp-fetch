# Laravel MCP Fetch

MCP fetch server matching the official fetch tool schema.

## Registration

Auto-registers local handle `fetch` when `laravel/mcp` is installed (override via `MCP_FETCH_LOCAL_HANDLE`).

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

## Docker

Image: `ghcr.io/miguelenes/laravel-mcp-fetch`

```json
{
    "mcpServers": {
        "fetch": {
            "command": "docker",
            "args": ["run", "-i", "--rm", "ghcr.io/miguelenes/laravel-mcp-fetch:latest"]
        }
    }
}
```
