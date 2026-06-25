<?php

declare(strict_types=1);

namespace Eloquage\LaravelMcpFetch\Mcp\Servers;

use Eloquage\LaravelMcpFetch\Mcp\Tools\FetchUrlTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('fetch')]
#[Version('1.0.0')]
#[Instructions(<<<'INSTRUCTIONS'
Use this server to retrieve content from a known URL.

Workflow:
1. Discover URLs via web search or other tools
2. Call `fetch` with the URL to extract readable text

Pagination: use start_index when a previous fetch was truncated.
Set raw=true only when you need the original HTML.
INSTRUCTIONS)]
class FetchServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        FetchUrlTool::class,
    ];
}
