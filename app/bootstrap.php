<?php
declare(strict_types=1);

/**
 * Swasti Homoeo Clinic — application bootstrap.
 *
 * Loaded by public_html/index.php. Detects the deployment layout, registers a
 * PSR-4 autoloader for the App\ namespace, loads .env + config and boots the
 * kernel. No Composer, no build step.
 *
 * @return App\Core\Kernel
 */

use App\Core\Config;
use App\Core\Env;
use App\Core\Kernel;
use App\Core\Paths;
use App\Core\Session;
use App\Core\View;

// ---------------------------------------------------------------------------
// 1. Resolve the directory layout (works beside public_html or inside it).
// ---------------------------------------------------------------------------
if (!class_exists(Paths::class, false)) {
    require_once __DIR__ . '/Core/Paths.php';
}

$publicPath = null;
$entry     = null;

if (PHP_SAPI === 'cli-server') {
    // `php -S 127.0.0.1:8000 -t public_html public_html/index.php`
    $docRoot   = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__) . '/public_html';
    $entry     = $_SERVER['SCRIPT_FILENAME'] ?? __DIR__ . '/../public_html/index.php';
    $publicPath = rtrim(str_replace('\\', '/', (string) $docRoot), '/');
} else {
    $entry     = $_SERVER['SCRIPT_FILENAME'] ?? __DIR__ . '/../public_html/index.php';

    // The public web root is the directory that owns the front controller. The
    // JSON endpoints under public_html/api/ are one level deeper, so walk up
    // until the directory holding index.php is found.
    $publicPath = (static function (string $entry): string {
        $dir = rtrim(str_replace('\\', '/', dirname($entry)), '/');

        for ($depth = 0; $depth < 4; $depth++) {
            if (is_file($dir . '/index.php')) {
                return $dir;
            }

            $parent = rtrim(str_replace('\\', '/', dirname($dir)), '/');
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        return rtrim(str_replace('\\', '/', dirname($entry)), '/');
    })($entry);
}

Paths::detect((string) $entry, (string) $publicPath);

// ---------------------------------------------------------------------------
// 2. PSR-4 autoloader (App\ => app/).
// ---------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = Paths::app() . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

// ---------------------------------------------------------------------------
// 3. Configuration.
require_once __DIR__ . '/helpers.php';

Env::load(Paths::root() . '/.env');

$configFile = Paths::config() . '/config.php';
$config     = is_file($configFile) ? require $configFile : [];
if (!is_array($config)) {
    $config = [];
}

Config::load($config);

// Environment overrides (Hostinger hPanel values always win over defaults).
Config::set('app.env', Env::string('APP_ENV', Config::string('app.env', 'production')));
Config::set('app.debug', Env::bool('APP_DEBUG', Config::bool('app.debug', false)));
Config::set('app.url', rtrim(Env::string('APP_URL', Config::string('app.url', '')), '/'));
Config::set('api.base_url', rtrim(Env::string('API_BASE_URL', Config::string('api.base_url', '')), '/'));
Config::set('app.timezone', Env::string('APP_TIMEZONE', Config::string('app.timezone', 'Asia/Kolkata')));
Config::set('app.locale', Env::string('APP_LOCALE', Config::string('app.locale', 'en_IN')));
Config::set('app.key', Env::string('APP_KEY', ''));

Config::set('db.host', Env::string('DB_HOST', Config::string('db.host', 'localhost')));
Config::set('db.port', Env::int('DB_PORT', Config::int('db.port', 3306)));
Config::set('db.database', Env::string('DB_DATABASE', Config::string('db.database', '')));
Config::set('db.username', Env::string('DB_USERNAME', Config::string('db.username', '')));
Config::set('db.password', Env::string('DB_PASSWORD', Config::string('db.password', '')));
Config::set('db.charset', Env::string('DB_CHARSET', Config::string('db.charset', 'utf8mb4')));
Config::set('db.socket', Env::string('DB_SOCKET', Config::string('db.socket', '')));
Config::set('mongodb.uri', Env::string('MONGODB_URI', Config::string('mongodb.uri', '')));
Config::set('mongodb.database', Env::string('MONGODB_DATABASE', Config::string('mongodb.database', 'swasti_homeo')));

$mailHost = Env::string('MAIL_HOST', Config::string('mail.host', ''));
Config::set('mail.host', $mailHost);
Config::set('mail.enabled', $mailHost !== '');
Config::set('mail.port', Env::int('MAIL_PORT', Config::int('mail.port', 587)));
Config::set('mail.username', Env::string('MAIL_USERNAME', Config::string('mail.username', '')));
Config::set('mail.password', Env::string('MAIL_PASSWORD', Config::string('mail.password', '')));
Config::set('mail.encryption', strtolower(Env::string('MAIL_ENCRYPTION', Config::string('mail.encryption', 'tls'))));
Config::set('mail.from_address', Env::string('MAIL_FROM_ADDRESS', Config::string('mail.from_address', '')));
Config::set('mail.from_name', Env::string('MAIL_FROM_NAME', Config::string('mail.from_name', 'Swasti Homoeo Clinic')));
Config::set('mail.reply_to', Env::string('MAIL_REPLY_TO', Config::string('mail.reply_to', '')));
Config::set('mail.to_address', Env::string('MAIL_TO_ADDRESS', Config::string('mail.to_address', '')));
Config::set('mail.cc', Env::string('MAIL_CC', Config::string('mail.cc', '')));

Config::set('admin.name', Env::string('ADMIN_NAME', Config::string('admin.name', 'Clinic Administrator')));
Config::set('admin.email', Env::string('ADMIN_EMAIL', Config::string('admin.email', '')));
Config::set('admin.password_hash', Env::string('ADMIN_PASSWORD_HASH', Config::string('admin.password_hash', '')));

Config::set('clinic.phone', Env::string('CLINIC_PHONE', Config::string('clinic.phone', '')));
Config::set('clinic.whatsapp', Env::string('CLINIC_WHATSAPP', Config::string('clinic.whatsapp', '')));
Config::set('clinic.email', Env::string('CLINIC_EMAIL', Config::string('clinic.email', '')));
Config::set('clinic.facebook', Env::string('CLINIC_FACEBOOK', Config::string('clinic.facebook', '')));
Config::set('clinic.instagram', Env::string('CLINIC_INSTAGRAM', Config::string('clinic.instagram', '')));
Config::set('clinic.x', Env::string('CLINIC_X', Config::string('clinic.x', '')));
Config::set('clinic.youtube', Env::string('CLINIC_YOUTUBE', Config::string('clinic.youtube', '')));

// ---------------------------------------------------------------------------
// 4. Views, sessions and shared view data.
// ---------------------------------------------------------------------------
View::setBasePath(Paths::views());

Session::start();

View::share('appName', Config::string('app.name', 'Swasti Homoeo Clinic'));
View::share('clinic', \App\Content\Clinic::info());
View::share('primaryNav', \App\Content\Clinic::navigation());
View::share('footerNav', \App\Content\Clinic::footerNavigation());
View::share('flashSuccess', Session::getFlash('success'));
View::share('flashError', Session::getFlash('error'));
View::share('flashedErrors', Session::flashErrors());
View::share('oldInput', Session::flashOld());
View::share('appYear', date('Y'));

// ---------------------------------------------------------------------------
// 5. Kernel.
// ---------------------------------------------------------------------------
$kernel = new Kernel();

require_once __DIR__ . '/routes.php';

return $kernel;
