<?php
declare(strict_types=1);

/**
 * Swasti Homoeo Clinic — front controller.
 *
 * Hostinger Business shared hosting entry point. Everything that is not a real
 * file or directory is routed here by .htaccess and dispatched by the kernel.
 *
 * The application resolves its own paths, so the same code works when the
 * application directory sits beside public_html/ and when the whole project is
 * uploaded inside public_html/ (see HOSTINGER_DEPLOYMENT.md).
 *
 * Requires PHP 8.1+ with pdo_mysql, mbstring, json, fileinfo, openssl and sessions.
 */

// ---------------------------------------------------------------------------
// 0. Hard guards for the single-folder deployment (sensitive files inside the
//    document root). .htaccess already blocks these; this is the belt to its
//    braces and is what the PHP built-in server relies on.
// ---------------------------------------------------------------------------
$__sh_request = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
$__sh_request = rawurldecode($__sh_request);

if (preg_match('#(^|/)\.(?!well-known)#', $__sh_request)
    || preg_match('#\.(sql|sql\.gz|bak|log|ini|sh|jsonl|md5|sha1|lock|dist|example)$#i', $__sh_request)
    || preg_match('#/(app|config|views|database|storage|logs|sessions|backups)/#i', $__sh_request)
) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Retry-After: 3600');
    echo "404 Not Found\n";
    exit;
}

// ---------------------------------------------------------------------------
// 1. Minimum PHP version guard.
// ---------------------------------------------------------------------------
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Server requirements</title></head><body style="font-family:system-ui,sans-serif;'
        . 'max-width:40rem;margin:4rem auto;padding:0 1.5rem;line-height:1.7;color:#16202e">'
        . '<h1>Server requirements are not met</h1>'
        . '<p>Swasti Homoeo Clinic needs <strong>PHP 8.1 or newer</strong>. This server is running '
        . 'PHP ' . PHP_VERSION . '.</p>'
        . '<p>In Hostinger hPanel open <strong>Websites &rarr; PHP</strong> and select PHP 8.1 or 8.2, '
        . 'then reload this page.</p></body></html>';
    exit;
}

// ---------------------------------------------------------------------------
// 2. Built-in PHP server: serve real static files and let the framework handle
//    everything else. Production (Apache/LiteSpeed) never reaches this branch.
// ---------------------------------------------------------------------------
if (PHP_SAPI === 'cli-server') {
    // The built-in server prefers index.php over index.html for `/`, while
    // Apache/LiteSpeed use the DirectoryIndex order configured in .htaccess.
    // Keep local previews aligned with production's static homepage.
    if ($__sh_request === '/' && is_file(__DIR__ . '/index.html')) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile(__DIR__ . '/index.html');
        exit;
    }

    $__sh_path = parse_url($__sh_request, PHP_URL_PATH) ?: '/';
    $__sh_file = __DIR__ . $__sh_path;

    // Vercel cleanUrls maps /about-us to about-us.html. Mirror that behavior
    // locally so static pages keep their local CSS and image asset URLs.
    if (!is_file($__sh_file)
        && pathinfo($__sh_path, PATHINFO_EXTENSION) === ''
        && is_file(__DIR__ . $__sh_path . '.html')) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile(__DIR__ . $__sh_path . '.html');
        exit;
    }

    if (is_file($__sh_file) && basename($__sh_file) !== 'index.php') {
        $__sh_ext = strtolower(pathinfo($__sh_file, PATHINFO_EXTENSION));
        $__sh_allow = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf',
            'eot', 'otf', 'txt', 'xml', 'json', 'webmanifest', 'pdf', 'mp4', 'webm', 'avif',
            // The front end is static HTML: these are the files Apache serves in
            // production, so the built-in server must serve them too.
            'html'];

        // .htaccess serves real files as-is, which is what makes /api/csrf.php
        // and /api/booking.php work without the framework. Mirror that here so
        // the JSON endpoints behave the same locally. Returning false makes the
        // built-in server run the script; only /api/*.php is ever let through.
        $__sh_rel = '/' . ltrim(str_replace('\\', '/', substr($__sh_file, strlen(__DIR__))), '/');
        $__sh_api_endpoint = $__sh_ext === 'php'
            && preg_match('#^/api/[A-Za-z0-9_-]+\.php$#', $__sh_rel) === 1;

        if (in_array($__sh_ext, $__sh_allow, true) || $__sh_api_endpoint) {
            return false;
        }
    }
}

// ---------------------------------------------------------------------------
// 3. Boot the application.
// ---------------------------------------------------------------------------
/** @var App\Core\Kernel $kernel */
$kernel = require __DIR__ . '/../app/bootstrap.php';

$request  = App\Core\Request::capture(__DIR__);
$response = $kernel->handle($request);

$response->send($request->isHttps());
