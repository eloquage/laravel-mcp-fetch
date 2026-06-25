<?php

declare(strict_types=1);

namespace Eloquage\LaravelMcpFetch;

use Eloquage\LaravelMcpFetch\Services\UrlFetchService;
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
}
