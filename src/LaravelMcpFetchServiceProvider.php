<?php

declare(strict_types=1);

namespace Eloquage\LaravelMcpFetch;

use Eloquage\LaravelMcpFetch\Mcp\Servers\FetchServer;
use Eloquage\LaravelMcpFetch\Services\UrlFetchService;
use Laravel\Mcp\Facades\Mcp;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMcpFetchServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mcp-fetch')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(UrlFetchService::class);
    }

    public function packageBooted(): void
    {
        if (! class_exists(Mcp::class)) {
            return;
        }

        Mcp::local(
            (string) config('mcp-fetch.mcp.local_handle', 'fetch'),
            FetchServer::class,
        );
    }
}
