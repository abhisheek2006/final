<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Immutable-ish wrapper around the current HTTP request.
 */
final class Request
{
    /** @var array<string,mixed> */
    private array $query;

    /** @var array<string,mixed> */
    private array $body;

    /** @var array<string,mixed> */
    private array $server;

    /** @var array<string,string> */
    private array $routeParams = [];

    /** @var array<string,mixed> */
    private array $attributes = [];

    private ?string $path = null;

    private ?string $method = null;

    public function __construct(?array $query = null, ?array $body = null, ?array $server = null)
    {
        $this->query  = $query ?? $_GET;
        $this->body   = $body ?? $_POST;
        $this->server = $server ?? $_SERVER;
    }

    public static function capture(string $publicPath): self
    {
        $request = new self($_GET, $_POST, $_SERVER);
        $request->method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $request->path   = self::normalisePath($request->server['REQUEST_URI'] ?? '/', $publicPath);

        return $request;
    }

    /**
     * Strip the front-controller directory and normalise the path.
     * Works both when the app is inside and outside the document root.
     */
    public static function normalisePath(string $uri, string $publicPath): string
    {
        $uri = strtok($uri, '?') ?: '/';
        $uri = rawurldecode($uri);

        // Absolute-path form of a URL.
        if (preg_match('#^https?://[^/]+(/.*)?$#i', $uri, $m)) {
            $uri = $m[1] ?? '/';
        }

        $uri = '/' . ltrim($uri, '/');

        $publicPath = rtrim(str_replace('\\', '/', $publicPath), '/');
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

        // Only a real PHP entry point may contribute a base directory. Apache and
        // LiteSpeed report /index.php (or /clinic/index.php); the PHP built-in
        // server reports the requested URI itself for extension-less paths such
        // as /articles/my-slug, and stripping that would eat a real route.
        $scriptFile = basename($scriptName);
        $scriptDir  = str_ends_with(strtolower($scriptFile), '.php')
            ? rtrim(str_replace('\\', '/', dirname($scriptName)), '/')
            : '';

        // Sub-directory installation: /clinic/public_html/index.php -> /clinic/public_html
        if ($scriptDir !== '' && $scriptDir !== '/'
            && ($uri === $scriptDir || str_starts_with($uri, $scriptDir . '/'))
        ) {
            $uri = substr($uri, strlen($scriptDir));
            $uri = '/' . ltrim($uri, '/');
        }

        $uri = self::collapse($uri);

        return $uri === '' ? '/' : $uri;
    }

    private static function collapse(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return rtrim($path, '/');
    }

    public function method(): string
    {
        return $this->method ?? strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function path(): string
    {
        return $this->path ?? '/';
    }

    public function is(string $path): bool
    {
        return rtrim($this->path(), '/') === rtrim($path, '/');
    }

    /** True for the built-in PHP server / CLI tooling. */
    public function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $value;
    }

    public function raw(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /** @return array<string,mixed> */
    public function body(): array
    {
        return $this->body;
    }

    /** @return array<string,mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$key]) && is_string($this->server[$key])) {
            return $this->server[$key];
        }
        if ($name === 'Content-Type' && isset($this->server['CONTENT_TYPE'])) {
            return (string) $this->server['CONTENT_TYPE'];
        }
        if ($name === 'User-Agent' && isset($this->server['HTTP_USER_AGENT'])) {
            return (string) $this->server['HTTP_USER_AGENT'];
        }

        return $default;
    }

    public function userAgent(): string
    {
        return substr((string) $this->header('User-Agent', ''), 0, 255);
    }

    /**
     * Client IP. X-Forwarded-For is only trusted for proxies listed in
     * config('security.trusted_proxies').
     */
    public function ip(): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '');
        $trusted = (array) Config::get('security.trusted_proxies', []);

        if ($remote !== '' && in_array($remote, $trusted, true)) {
            $forwarded = (string) $this->header('X-Forwarded-For', '');
            foreach (explode(',', $forwarded) as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                    return $candidate;
                }
            }
        }

        if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP) !== false) {
            return $remote;
        }

        return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '0.0.0.0';
    }

    public function isHttps(): bool
    {
        if (($this->server['HTTPS'] ?? '') !== '' && strtolower((string) $this->server['HTTPS']) !== 'off') {
            return true;
        }
        if ((int) ($this->server['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        if (strtolower((string) $this->header('X-Forwarded-Proto', '')) === 'https') {
            return true;
        }

        return false;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        $accept = strtolower((string) $this->header('Accept', ''));

        return str_contains($accept, 'application/json')
            || $this->isAjax()
            || strtolower((string) $this->header('Content-Type', '')) === 'application/json';
    }

    public function isBot(): bool
    {
        $ua = strtolower($this->userAgent());
        foreach (['bot', 'crawler', 'spider', 'curl/', 'wget', 'python-requests', 'scrapy', 'headlesschrome'] as $needle) {
            if (str_contains($ua, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function referrer(): string
    {
        return substr((string) $this->header('Referer', ''), 0, 255);
    }

    public function setRouteParam(string $key, string $value): void
    {
        $this->routeParams[$key] = $value;
    }

    public function routeParam(string $key, string $default = ''): string
    {
        return $this->routeParams[$key] ?? $default;
    }

    /** @return array<string,string> */
    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /** Absolute URL for an internal path. */
    public function url(string $path = '/'): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');

        // In local development, follow the host and port used for this request.
        // APP_URL may be left on another port from a previous dev-server run.
        if (Config::string('app.env', '') === 'local') {
            $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($this->server['HTTP_HOST'] ?? '')) ?? '';
            if ($host !== '') {
                $base = ($this->isHttps() ? 'https' : 'http') . '://' . $host;
            }
        }

        $path = '/' . ltrim($path, '/');

        if ($path === '/') {
            return $base !== '' ? $base . '/' : '/';
        }

        return $base . $path;
    }

    /** Path only, with a marker when APP_URL does not match the live host. */
    public function pathFor(string $path = '/'): string
    {
        return '/' . ltrim($path, '/');
    }

    public function fullUrl(): string
    {
        $scheme = $this->isHttps() ? 'https' : 'http';
        $host   = (string) ($this->server['HTTP_HOST'] ?? Config::get('app.url', 'localhost'));
        $host   = preg_replace('/[^A-Za-z0-9\.\-:\[\]]/', '', $host) ?? 'localhost';

        return $scheme . '://' . $host . $this->path();
    }
}
