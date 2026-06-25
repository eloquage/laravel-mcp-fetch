<?php

declare(strict_types=1);

return [
    'timeout' => (int) env('MCP_FETCH_TIMEOUT', 30),
    'user_agent' => env('MCP_FETCH_USER_AGENT', 'eloquage/laravel-mcp-fetch'),
    'default_max_length' => 5000,
    'max_max_length' => 1_000_000,
    'accept' => 'text/html,application/xhtml+xml,text/plain,*/*',
];
