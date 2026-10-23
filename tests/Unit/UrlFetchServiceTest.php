<?php

use Eloquage\LaravelMcpFetch\Services\UrlFetchService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('fetches and simplifies html content', function () {
    Http::fake([
        'https://example.com/page' => Http::response('<html><body><h1>Title</h1><p>Body text</p></body></html>'),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/page', maxLength: 5000);

    expect($result['success'])->toBeTrue()
        ->and($result['content'])->toContain('Title')
        ->and($result['content'])->toContain('Body text')
        ->and($result['content_length'])->toBeGreaterThan(0)
        ->and($result['start_index'])->toBe(0);
});

it('pins DNS-resolved addresses and bypasses proxies', function () {
    $method = new ReflectionMethod(UrlFetchService::class, 'requestOptions');
    $options = $method->invoke(new UrlFetchService, 'example.com:443:93.184.216.34');

    expect($options['curl'][CURLOPT_RESOLVE])->toBe(['example.com:443:93.184.216.34'])
        ->and($options['proxy'])->toBe(['http' => '', 'https' => '']);
});

it('extracts main content from article tags', function () {
    Http::fake([
        'https://example.com/article' => Http::response(
            '<html><div class="sidebar">Sidebar junk</div><article><h1>Main Title</h1><p>Main body</p></article></html>'
        ),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/article');

    expect($result['success'])->toBeTrue()
        ->and($result['content'])->toContain('Main Title')
        ->and($result['content'])->toContain('Main body')
        ->and($result['content'])->not->toContain('Sidebar junk');
});

it('truncates content to max length', function () {
    Http::fake([
        'https://example.com/long' => Http::response('<html><body>'.str_repeat('x', 100).'</body></html>'),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/long', maxLength: 50);

    expect($result['success'])->toBeTrue()
        ->and($result['truncated'])->toBeTrue()
        ->and(strlen($result['content']))->toBe(50)
        ->and($result['total_length'])->toBe(100);
});

it('paginates content with start index', function () {
    Http::fake([
        'https://example.com/paged' => Http::response('<html><body>abcdefghij</body></html>'),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/paged', maxLength: 100, startIndex: 5);

    expect($result['success'])->toBeTrue()
        ->and($result['content'])->toBe('fghij')
        ->and($result['start_index'])->toBe(5);
});

it('returns raw html when requested', function () {
    Http::fake([
        'https://example.com/raw' => Http::response('<html><body>Raw</body></html>'),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/raw', raw: true);

    expect($result['content'])->toContain('<html>');
});

it('returns error on http failure', function () {
    Http::fake([
        'https://example.com/missing' => Http::response('Not Found', 404),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/missing');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('HTTP 404');
});

it('does not follow redirects to private hosts', function () {
    Http::fake([
        'https://example.com/redirect' => Http::response('', 302, [
            'Location' => 'http://127.0.0.1/admin',
        ]),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/redirect');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('HTTP 302');

    Http::assertSentCount(1);
});

it('returns error on server error', function () {
    Http::fake([
        'https://example.com/error' => Http::response('Error', 500),
    ]);

    $result = (new UrlFetchService)->fetch('https://example.com/error');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('HTTP 500');
});

it('returns error on connection failure', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    $result = (new UrlFetchService)->fetch('https://example.com/timeout');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Connection timed out');
});

it('rejects non-http schemes', function () {
    $result = (new UrlFetchService)->fetch('file:///etc/passwd');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Only http and https');
});

it('rejects localhost urls', function () {
    $result = (new UrlFetchService)->fetch('http://localhost/admin');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects private ip addresses', function () {
    $result = (new UrlFetchService)->fetch('http://192.168.1.1/internal');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects 10.x private ip addresses', function () {
    $result = (new UrlFetchService)->fetch('http://10.0.0.1/internal');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects link-local ip addresses', function () {
    $result = (new UrlFetchService)->fetch('http://169.254.1.1/metadata');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects private ipv6 addresses', function () {
    $result = (new UrlFetchService)->fetch('http://[fc00::1]/internal');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects 172.16.x private ip addresses', function () {
    $result = (new UrlFetchService)->fetch('http://172.16.0.1/internal');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects shared carrier-grade NAT addresses', function () {
    $result = (new UrlFetchService)->fetch('http://100.64.0.1/internal');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Private or local');
});

it('rejects non-global documentation addresses', function () {
    $ipv4 = (new UrlFetchService)->fetch('http://203.0.113.1/example');
    $ipv6 = (new UrlFetchService)->fetch('http://[2001:db8::1]/example');

    expect($ipv4['success'])->toBeFalse()
        ->and($ipv4['error'])->toContain('Private or local')
        ->and($ipv6['success'])->toBeFalse()
        ->and($ipv6['error'])->toContain('Private or local');
});
