<?php

namespace Eloquage\LaravelMcpFetch\Tests;

use Eloquage\LaravelMcpFetch\LaravelMcpFetchServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            LaravelMcpFetchServiceProvider::class,
        ];
    }
}
