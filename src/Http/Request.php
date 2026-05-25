<?php

declare(strict_types=1);

namespace LovelyWedding\Http;

final class Request
{
    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function __construct(
        public readonly array $get,
        public readonly array $post,
        public readonly array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $v = $this->get[$key] ?? $default;

        return is_scalar($v) ? (string) $v : $default;
    }

    public function post(string $key, ?string $default = null): ?string
    {
        $v = $this->post[$key] ?? $default;

        return is_scalar($v) ? (string) $v : $default;
    }

    public function clientIp(): string
    {
        $ip = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');

        return $ip === '' ? '0.0.0.0' : $ip;
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
