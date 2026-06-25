<?php

use Eloquage\LaravelMcpFetch\Mcp\Tools\FetchUrlTool;
use Eloquage\LaravelMcpFetch\Services\UrlFetchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('returns fetched content as json', function () {
    Http::fake([
        'https://example.com/*' => Http::response('<html><body>Hello world</body></html>'),
    ]);

    $response = app(FetchUrlTool::class)->handle(
        new Request(['url' => 'https://example.com/test']),
        app(UrlFetchService::class),
    );

    expect($response)->toBeInstanceOf(Response::class);

    $payload = json_decode((string) $response->content(), true);

    expect($payload['success'])->toBeTrue()
        ->and($payload['content'])->toContain('Hello world')
        ->and($payload)->toHaveKeys(['start_index', 'content_length', 'total_length']);
});

it('returns raw html when raw is true', function () {
    Http::fake([
        'https://example.com/*' => Http::response('<html><body>Raw</body></html>'),
    ]);

    $response = app(FetchUrlTool::class)->handle(
        new Request(['url' => 'https://example.com/raw', 'raw' => true]),
        app(UrlFetchService::class),
    );

    $payload = json_decode((string) $response->content(), true);

    expect($payload['success'])->toBeTrue()
        ->and($payload['content'])->toContain('<html>');
});

it('returns fetch error as json', function () {
    Http::fake([
        'https://example.com/*' => Http::response('Not Found', 404),
    ]);

    $response = app(FetchUrlTool::class)->handle(
        new Request(['url' => 'https://example.com/missing']),
        app(UrlFetchService::class),
    );

    $payload = json_decode((string) $response->content(), true);

    expect($payload['success'])->toBeFalse()
        ->and($payload['error'])->toContain('HTTP 404');
});

it('rejects invalid urls', function () {
    app(FetchUrlTool::class)->handle(
        new Request(['url' => 'not-a-valid-url']),
        app(UrlFetchService::class),
    );
})->throws(ValidationException::class);

it('rejects max length over one million', function () {
    app(FetchUrlTool::class)->handle(
        new Request(['url' => 'https://example.com/test', 'max_length' => 1_000_001]),
        app(UrlFetchService::class),
    );
})->throws(ValidationException::class);
