<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;
use App\Services\Seo;
use App\Services\MaintenanceMode;
use Throwable;

/**
 * Application kernel: boots configuration, dispatches the request, converts
 * exceptions into safe HTTP responses and applies security headers.
 */
final class Kernel
{
    private Router $router;

    private bool $booted = false;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        $this->registerErrorHandling();

        date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Kolkata'));
        mb_internal_encoding('UTF-8');

        $this->ensureStorage();

        // Force HTTPS in production.
        if (Config::bool('security.force_https', true)) {
            $request = Request::capture(Paths::public());
            if (!$request->isCli() && !$request->isHttps() && $this->shouldForceHttps($request)) {
                $this->redirectToHttps($request);
            }
        }
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function handle(Request $request): Response
    {
        $this->boot();

        try {
            $path = rtrim($request->path(), '/') ?: '/';
            $admin = $path === '/admin' || str_starts_with($path, '/admin/');
            $operational = in_array($path, ['/health', '/robots.txt', '/sitemap.xml'], true);
            $maintenance = (!$admin && !$operational) ? MaintenanceMode::publicStatus() : ['enabled' => false];

            if (!empty($maintenance['enabled'])) {
                $message = htmlspecialchars((string) $maintenance['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html = '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
                    . '<title>Website maintenance · Swasti Homoeo Clinic</title><body style="margin:0;font:16px/1.6 system-ui,sans-serif;background:#f4f8f7;color:#183b3a;display:grid;min-height:100vh;place-items:center;padding:24px;box-sizing:border-box">'
                    . '<main style="max-width:620px;padding:48px;background:#fff;border:1px solid #dce8e5;border-radius:20px;text-align:center;box-shadow:0 16px 48px rgba(21,73,67,.08)"><p style="color:#0f6f68;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Swasti Homoeo Clinic</p><h1 style="font-family:Georgia,serif;font-size:clamp(2rem,7vw,3rem);line-height:1.1">We’ll be back soon</h1><p>' . $message . '</p><a href="tel:+917980644867" style="display:inline-block;margin-top:16px;color:#0f6f68;font-weight:700">Call the clinic</a></main></body></html>';
                $response = (new Response($html, 503))
                    ->setHeader('Content-Type', 'text/html; charset=UTF-8')
                    ->noCache();
            } else {
                $response = $this->router->dispatch($request);
            }
        } catch (HttpException $e) {
            $response = $this->renderHttpException($request, $e);
        } catch (Throwable $e) {
            $response = $this->renderThrowable($request, $e);
        }

        return $this->finalise($request, $response);
    }

    private function finalise(Request $request, Response $response): Response
    {
        if (!$response->hasHeader('X-Content-Type-Options')) {
            $response->withSecurityHeaders($request->isHttps(), $this->isAdminPath($request));
        }

        if (!$response->isRedirect() && !$response->hasHeader('Content-Security-Policy')) {
            $response->withCsp($request->isHttps());
        }

        return $response;
    }

    private function isAdminPath(Request $request): bool
    {
        $path = rtrim($request->path(), '/');

        return $path === '/admin' || str_starts_with($path, '/admin/');
    }

    private function renderHttpException(Request $request, HttpException $e): Response
    {
        $response = $e->toResponse();

        if ($response->isRedirect()) {
            return $response;
        }

        $status = $e->statusCode();
        $titles = [
            400 => 'Bad request',
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Method not allowed',
            419 => 'Session expired',
            422 => 'Unable to process request',
            429 => 'Too many requests',
            500 => 'Something went wrong',
            503 => 'Temporarily unavailable',
        ];

        if ($request->wantsJson()) {
            return $response->json([
                'ok'    => false,
                'error' => $e->getMessage() !== '' ? $e->getMessage() : ($titles[$status] ?? 'Error'),
            ], $status);
        }

        $template = $status === 404 ? 'pages/404' : 'errors/error';
        $data     = [
            'status'      => $status,
            'heading'     => $titles[$status] ?? 'Error',
            'message'     => $status === 404
                ? 'The page you are looking for may have been moved, renamed or never existed.'
                : $e->getMessage(),
            'exception'   => Config::debug() ? $e : null,
            'requestPath' => $request->path(),
            'seo'         => Seo::defaults([
                'title'       => ($titles[$status] ?? 'Error') . ' — ' . Config::get('app.name'),
                'description' => 'The requested page could not be loaded.',
                'noindex'     => true,
            ], $request),
        ];

        try {
            $layout = $this->isAdminPath($request) ? 'layouts/admin' : 'layouts/public';
            if (!View::exists($layout)) {
                $layout = 'layouts/public';
            }
            $html = View::render($template, $data, $layout);

            return (new Response($html, $status))
                ->setHeader('Content-Type', 'text/html; charset=UTF-8')
                ->noCache();
        } catch (Throwable) {
            return $response->setBody(self::fallbackErrorHtml($status, $titles[$status] ?? 'Error'));
        }
    }

    private function renderThrowable(Request $request, Throwable $e): Response
    {
        Logger::error('kernel', $e->getMessage(), [
            'exception' => $e::class,
            'file'      => $e->getFile() . ':' . $e->getLine(),
            'path'      => $request->path(),
            'ip'        => $request->ip(),
        ]);

        $status = 500;
        $e      = new HttpException($status, Config::debug() ? $e->getMessage() : 'Internal server error.');

        return $this->renderHttpException($request, $e);
    }

    public static function fallbackErrorHtml(int $status, string $heading): string
    {
        $safeStatus  = (int) $status;
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
        $home        = htmlspecialchars((string) Config::get('app.url', '/'), ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $safeStatus . ' · ' . $safeHeading . '</title>'
            . '<link rel="stylesheet" href="' . $home . '/assets/css/main.css"></head>'
            . '<body class="error-body"><main class="error-shell"><p class="error-shell__code">' . $safeStatus . '</p>'
            . '<h1 class="error-shell__title">' . $safeHeading . '</h1>'
            . '<p class="error-shell__text">The page you requested could not be loaded.</p>'
            . '<p><a class="btn btn--primary" href="' . $home . '/">Back to home</a></p>'
            . '</main></body></html>';
    }

    /* ------------------------------- boot -------------------------------- */

    private function registerErrorHandling(): void
    {
        $debug = Config::debug();

        error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', Paths::storage() . '/logs/php-error.log');

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }
            if ((str_contains($file, DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR) || str_contains($file, DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR)) && !Config::debug()) {
                Logger::error('php', $message, ['file' => $file . ':' . $line, 'severity' => $severity]);
            }

            return true;
        });

        set_exception_handler(static function (Throwable $e): void {
            Logger::error('uncaught', $e->getMessage(), [
                'exception' => $e::class,
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
            }

            echo self::fallbackErrorHtml(500, 'Something went wrong');
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }
            Logger::error('fatal', $error['message'] ?? 'Fatal error', ['file' => $error['file'] ?? '?']);
        });
    }

    private function ensureStorage(): void
    {
        $storage = Paths::storage();
        foreach (['', '/submissions', '/mail', '/logs', '/sessions'] as $sub) {
            Paths::ensureWritable($storage . $sub);
        }

        foreach ([
            '/submissions/.htaccess',
            '/mail/.htaccess',
            '/logs/.htaccess',
            '/sessions/.htaccess',
            '/.htaccess',
        ] as $guard) {
            $file = $storage . $guard;
            if (!is_file($file)) {
                @file_put_contents($file, self::storageGuardBody());
            }
        }
    }

    private static function storageGuardBody(): string
    {
        return "# Swasti Homoeo Clinic - deny all web access to runtime storage.\n"
            . "Require all denied\n"
            . "Deny from all\n"
            . "Options -Indexes -ExecCGI\n"
            . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
    }

    private function shouldForceHttps(Request $request): bool
    {
        $host = strtolower(trim((string) $request->server('HTTP_HOST', '')));
        if ($host === '') {
            return false;
        }
        // Compare without the port: "127.0.0.1:842" is still loopback.
        $hostName = strtolower((string) preg_replace('/:\d+$/', '', $host));
        if (in_array($hostName, ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            return false;
        }
        $cliHosts = [];
        foreach ((array) Config::get('security.trusted_proxies', []) as $proxy) {
            $cliHosts[] = strtolower((string) preg_replace('/:\d+$/', '', trim((string) $proxy)));
        }

        return !in_array($hostName, $cliHosts, true);
    }

    private function redirectToHttps(Request $request): void
    {
        $host   = (string) $request->server('HTTP_HOST', '');
        $uri    = (string) $request->server('REQUEST_URI', '/');
        if (!str_starts_with($uri, '/') || str_contains($uri, "\r") || str_contains($uri, "\n")) {
            $uri = '/';
        }
        $target = 'https://' . $host . $uri;

        if (!headers_sent()) {
            http_response_code(301);
            header('Location: ' . $target, true);
            header('Strict-Transport-Security: max-age=15552000; includeSubDomains', true);
        }

        exit;
    }
}
