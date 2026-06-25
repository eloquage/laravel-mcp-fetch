<?php

declare(strict_types=1);

namespace Eloquage\LaravelMcpFetch\Mcp\Tools;

use Eloquage\LaravelMcpFetch\Mcp\Concerns\HandlesJsonToolResponse;
use Eloquage\LaravelMcpFetch\Services\UrlFetchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('fetch')]
#[Description('Fetches a URL from the internet and optionally extracts its contents as markdown.')]
class FetchUrlTool extends Tool
{
    use HandlesJsonToolResponse;

    public function handle(Request $request, UrlFetchService $fetcher): Response
    {
        $maxMaxLength = (int) config('mcp-fetch.max_max_length', 1_000_000);

        $request->validate([
            'url' => 'required|url',
            'max_length' => "nullable|integer|min:1|max:{$maxMaxLength}",
            'start_index' => 'nullable|integer|min:0',
            'raw' => 'nullable|boolean',
        ]);

        return $this->jsonResponse(function () use ($request, $fetcher): array {
            $result = $fetcher->fetch(
                $request->string('url')->toString(),
                $request->integer('max_length', (int) config('mcp-fetch.default_max_length', 5000)),
                $request->integer('start_index', 0),
                $request->boolean('raw', false),
            );

            if (! $result['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Fetch failed.'];
            }

            return $result;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('URL to fetch.')->required(),
            'max_length' => $schema->integer()->description('Maximum number of characters to return (default 5000).'),
            'start_index' => $schema->integer()->description('On return output starting at this character index, useful if a previous fetch was truncated and more context is required.'),
            'raw' => $schema->boolean()->description('Get the actual HTML content of the requested page, without simplification.'),
        ];
    }
}
