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
        $validation = $this->validatePublicUrl($url);

        if (isset($validation['error'])) {
            return [
                'success' => false,
                'error' => $validation['error'],
            ];
        }

        try {
            $response = Http::timeout((int) config('mcp-fetch.timeout', 30))
                ->withOptions($this->requestOptions($validation['resolve']))
                ->withoutRedirecting()
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

    /**
     * @return array{error: string}|array{resolve: string|null}
     */
    private function validatePublicUrl(string $url): array
    {
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return ['error' => 'Only http and https URLs are allowed.'];
        }

        $host = strtolower(trim($parsed['host'] ?? '', '[]'));

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return ['error' => 'Private or local URLs are not allowed.'];
        }

        $isIpAddress = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $ips = $isIpAddress
            ? [$host]
            : array_values(array_filter(array_map(
                static fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null,
                dns_get_record($host, DNS_A | DNS_AAAA) ?: [],
            )));

        if ($ips === []) {
            return ['error' => "Could not resolve host: {$host}"];
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return ['error' => 'Private or local URLs are not allowed.'];
            }
        }

        if ($isIpAddress) {
            return ['resolve' => null];
        }

        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        $addresses = implode(',', array_map(
            static fn (string $ip): string => str_contains($ip, ':') ? "[{$ip}]" : $ip,
            $ips,
        ));

        return ['resolve' => "{$host}:{$port}:{$addresses}"];
    }

    /** @return array{proxy: array{http: string, https: string}, curl?: array<int, list<string>>} */
    private function requestOptions(?string $resolve): array
    {
        $options = [
            // A proxy resolves the destination itself, outside this IP validation.
            'proxy' => ['http' => '', 'https' => ''],
        ];

        if ($resolve !== null) {
            $options['curl'] = [CURLOPT_RESOLVE => [$resolve]];
        }

        return $options;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_GLOBAL_RANGE,
        ) !== false;
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
