<?php

declare(strict_types=1);

it('auto-registers the fetch mcp server handle', function () {
    expect(config('mcp-fetch.mcp.local_handle'))->toBe('fetch');

    $this->artisan('mcp:start', ['handle' => 'fetch', '--help' => true])
        ->assertSuccessful();
});
