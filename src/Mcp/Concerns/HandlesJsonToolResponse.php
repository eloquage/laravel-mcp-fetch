<?php

declare(strict_types=1);

namespace Eloquage\LaravelMcpFetch\Mcp\Concerns;

use Laravel\Mcp\Response;
use Throwable;

trait HandlesJsonToolResponse
{
    /**
     * @param  callable(): array<string, mixed>  $callback
     */
    protected function jsonResponse(callable $callback): Response
    {
        try {
            return Response::text(json_encode($callback(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $exception) {
            return $this->serviceError($exception);
        }
    }

    protected function serviceError(Throwable $exception): Response
    {
        return Response::text(json_encode([
            'success' => false,
            'error' => $exception->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
