<?php

declare(strict_types=1);

namespace LovelyWedding\Http;

final class Router
{
    /**
     * @param array<string, array{0: class-string, 1: string}> $routes
     */
    public function __construct(private readonly array $routes)
    {
    }

    /**
     * @return array{0: class-string, 1: string}|null
     */
    public function match(string $path): ?array
    {
        $path = $this->normalize($path);

        if (isset($this->routes[$path])) {
            return $this->routes[$path];
        }

        // BC: accept trailing/leading variations of `.php`
        if (str_ends_with($path, '.php')) {
            $alt = substr($path, 0, -4);
            if (isset($this->routes[$alt])) {
                return $this->routes[$alt];
            }
        } else {
            $alt = $path.'.php';
            if (isset($this->routes[$alt])) {
                return $this->routes[$alt];
            }
        }

        return null;
    }

    private function normalize(string $path): string
    {
        if ('' === $path) {
            return '/';
        }
        if ('/' !== $path && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        if (!str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return $path;
    }
}
