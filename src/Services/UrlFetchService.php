<?php

declare(strict_types=1);

namespace Eloquage\LaravelMcpFetch\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class UrlFetchService
{
    /**
     * @return array{success: bool, content?: string, url?: string, truncated?: bool, start_index?: int, content_length?: int, total_length?: int, error?: string}
     */
    public function fetch(
        string $url,
        int $maxLength = 5000,
        int $startIndex = 0,
        bool $raw = false,
    ): array {
        $blocked = $this->rejectPrivateUrl($url);

        if ($blocked !== null) {
            return [
                'success' => false,
                'error' => $blocked,
            ];
        }

        try {
            $response = Http::timeout((int) config('mcp-fetch.timeout', 30))
                ->withHeaders([
                    'User-Agent' => (string) config('mcp-fetch.user_agent', 'eloquage/laravel-mcp-fetch'),
                    'Accept' => (string) config('mcp-fetch.accept', 'text/html,application/xhtml+xml,text/plain,*/*'),
                ])
                ->get($url);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()} fetching {$url}",
                ];
            }

            $body = $response->body();
            $content = $raw ? $body : $this->htmlToText($body);

            if ($startIndex > 0) {
                $content = substr($content, $startIndex);
            }

            $totalLength = strlen($content);
            $truncated = $totalLength > $maxLength;

            if ($truncated) {
                $content = substr($content, 0, $maxLength);
            }

            return [
                'success' => true,
                'url' => $url,
                'content' => $content,
                'truncated' => $truncated,
                'start_index' => $startIndex,
                'content_length' => strlen($content),
                'total_length' => $totalLength,
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function rejectPrivateUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return 'Only http and https URLs are allowed.';
        }

        $host = strtolower($parsed['host'] ?? '');

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'], true)) {
            return 'Private or local URLs are not allowed.';
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? [$host]
            : gethostbynamel($host);

        if ($ips === false || $ips === []) {
            return "Could not resolve host: {$host}";
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                return 'Private or local URLs are not allowed.';
            }
        }

        return null;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private function htmlToText(string $html): string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $html = preg_replace('/<(script|style|noscript|nav|header|footer|aside)[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = $this->extractMainContent($html);
        $html = preg_replace('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', "\n\n$2\n\n", $html) ?? $html;
        $html = preg_replace('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $html) ?? $html;
        $html = preg_replace('/<\/(p|div|br|li|tr)>/i', "\n", $html) ?? $html;
        $html = strip_tags($html);
        $html = preg_replace("/[ \t]+/", ' ', $html) ?? $html;
        $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;

        return trim($html);
    }

    private function extractMainContent(string $html): string
    {
        if (preg_match('/<(?:main|article)[^>]*>(.*)<\/(?:main|article)>/is', $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/<div[^>]+(?:id|class)=["\'][^"\']*(?:content|main|article)[^"\']*["\'][^>]*>(.*)<\/div>/is', $html, $matches) === 1) {
            return $matches[1];
        }

        return $html;
    }
}
