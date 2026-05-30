<?php

declare(strict_types=1);

namespace LovelyWedding\Service;

final class CsrfTokenManager
{
    private const SESSION_KEY = '_csrf_tokens';

    public function token(string $intent = 'default'): string
    {
        $this->ensureSession();
        if (!isset($_SESSION[self::SESSION_KEY][$intent])) {
            $_SESSION[self::SESSION_KEY][$intent] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY][$intent];
    }

    public function isValid(string $intent, ?string $token): bool
    {
        if (null === $token || '' === $token) {
            return false;
        }
        $this->ensureSession();
        $expected = $_SESSION[self::SESSION_KEY][$intent] ?? null;
        if (!is_string($expected)) {
            return false;
        }

        return hash_equals($expected, $token);
    }

    private function ensureSession(): void
    {
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }
}
