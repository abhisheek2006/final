<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Paths;
use App\Core\Session;
use App\Core\View;

/**
 * Global template helpers. Kept deliberately small: escaping, URLs, dates and
 * small formatting utilities used across views.
 */

if (!function_exists('e')) {
    /** Escape for HTML text/attribute context. */
    function e(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }
        if (is_array($value)) {
            $value = implode(', ', array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $value));
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('e_attr')) {
    /** Escape for use inside a double-quoted HTML attribute. */
    function e_attr(mixed $value): string
    {
        return e($value);
    }
}

if (!function_exists('e_js')) {
    /** Escape a value for embedding inside a <script type="application/ld+json"> block. */
    function e_js(mixed $value): string
    {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return $json === false ? 'null' : $json;
    }
}

if (!function_exists('json_ld')) {
    /** Print a JSON-LD block. Hashes are added to the CSP automatically. */
    function json_ld(array $data): string
    {
        return '<script type="application/ld+json">' . e_js($data) . "</script>\n";
    }
}

if (!function_exists('url')) {
    /** Absolute URL for an internal path. */
    function url(string $path = '/'): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');

        // Keep locally generated links on the exact host and port being used.
        if (Config::string('app.env', '') === 'local') {
            $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '';
            if ($host !== '') {
                $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
                $base = ($isHttps ? 'https' : 'http') . '://' . $host;
            }
        }

        $path = '/' . ltrim($path, '/');

        if ($path === '/') {
            return $base . '/';
        }

        return $base . $path;
    }
}

if (!function_exists('asset')) {
    /** Cache-busted asset URL (mtime based, no build step). */
    function asset(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $file = Paths::public() . $path;
        $version = is_file($file) ? substr((string) filemtime($file), -6) : '1';

        // Assets should stay on the current origin. APP_URL may point at the
        // production host (or a different local port) while developing locally.
        return $path . '?v=' . $version;
    }
}

if (!function_exists('api_url')) {
    /**
     * URL for a PHP form endpoint.
     *
     * Defaults to a same-origin path. When API_BASE_URL points at a different
     * host (static pages on Vercel, PHP backend on Hostinger) the absolute URL
     * is returned so the form posts cross-origin.
     */
    function api_url(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $base = rtrim((string) Config::string('api.base_url', ''), '/');

        return $base !== '' ? $base . $path : $path;
    }
}

if (!function_exists('image_url')) {
    function image_url(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return is_file(Paths::public() . $path) ? url($path) : url('/assets/images/icons/logo.png');
    }
}

if (!function_exists('has_image')) {
    function has_image(string $path): bool
    {
        $path = '/' . ltrim($path, '/');

        return is_file(Paths::public() . $path);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Session::csrfField();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('old')) {
    /** Repopulate a form field after a validation failure. */
    function old(string $key, mixed $default = ''): string
    {
        $old = View::shared()['oldInput'] ?? [];

        return e(is_array($old) ? ($old[$key] ?? $default) : $default);
    }
}

if (!function_exists('old_raw')) {
    function old_raw(string $key, mixed $default = ''): mixed
    {
        $old = View::shared()['oldInput'] ?? [];

        return is_array($old) ? ($old[$key] ?? $default) : $default;
    }
}

if (!function_exists('error_for')) {
    function error_for(string $key): string
    {
        $errors = View::shared()['flashedErrors'] ?? [];

        return is_array($errors) ? e($errors[$key] ?? '') : '';
    }
}

if (!function_exists('has_error')) {
    function has_error(string $key): bool
    {
        $errors = View::shared()['flashedErrors'] ?? [];

        return is_array($errors) && isset($errors[$key]) && $errors[$key] !== '';
    }
}

if (!function_exists('component')) {
    function component(string $name, array $data = []): string
    {
        return View::partial($name, $data);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Config::get('clinic.' . $key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('whatsapp_link')) {
    function whatsapp_link(string $message = ''): string
    {
        $number = preg_replace('/\D+/', '', (string) setting('whatsapp', '')) ?? '';
        $link   = 'https://wa.me/' . $number;

        if ($message !== '') {
            $link .= '?text=' . rawurlencode($message);
        }

        return $link;
    }
}

if (!function_exists('tel_link')) {
    function tel_link(?string $number = null): string
    {
        $number = $number ?? (string) setting('phone', '');
        $clean  = preg_replace('/[^0-9\+]/', '', $number) ?? '';

        return 'tel:' . $clean;
    }
}

if (!function_exists('mail_link')) {
    function mail_link(string $subject = ''): string
    {
        $link = 'mailto:' . (string) setting('email', '');

        return $subject === '' ? $link : $link . '?subject=' . rawurlencode($subject);
    }
}

if (!function_exists('format_phone')) {
    /** Render +91 79806 44867 style display text. */
    function format_phone(?string $number = null): string
    {
        $number = $number ?? (string) setting('phone', '');
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (strlen($digits) === 10) {
            return '+91 ' . substr($digits, 0, 5) . ' ' . substr($digits, 5);
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+91 ' . substr($digits, 2, 5) . ' ' . substr($digits, 7);
        }

        return trim($number);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if ($date === null || trim($date) === '') {
            return '';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return e($date);
        }

        return date($format, $ts);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $date, string $format = 'd M Y, h:i A'): string
    {
        if ($date === null || trim($date) === '') {
            return '';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return e($date);
        }

        return date($format, $ts);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return '';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return e($date);
        }

        $diff = time() - $ts;
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            $m = (int) floor($diff / 60);

            return $m . ' min ago';
        }
        if ($diff < 86400) {
            $h = (int) floor($diff / 3600);

            return $h . ($h === 1 ? ' hour ago' : ' hours ago');
        }
        if ($diff < 2592000) {
            $d = (int) floor($diff / 86400);

            return $d . ($d === 1 ? ' day ago' : ' days ago');
        }

        return date('d M Y', $ts);
    }
}

if (!function_exists('truncate')) {
    function truncate(?string $text, int $limit = 160, string $end = '…'): string
    {
        $text = trim(strip_tags((string) $text));
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $sp  = mb_strrpos($cut, ' ');

        return rtrim($sp !== false ? mb_substr($cut, 0, $sp) : $cut, " ,.;:-") . $end;
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $html, int $words = 30): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?? '');
        if ($text === '') {
            return '';
        }
        $parts = explode(' ', $text);
        if (count($parts) <= $words) {
            return $text;
        }

        return rtrim(implode(' ', array_slice($parts, 0, $words)), ' ,.;:-') . '…';
    }
}

if (!function_exists('narrative')) {
    /**
     * Convert simple authored HTML blocks to a safe, minimal subset.
     * Only <p>, <h3>, <h4>, <ul>, <ol>, <li>, <strong>, <em>, <br> survive.
     */
    function narrative(string $html): string
    {
        $allowed = '<p><h3><h4><h5><ul><ol><li><strong><em><br><blockquote>';
        $clean   = strip_tags($html, $allowed);
        $clean   = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean   = preg_replace('/\s(href|src)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;

        return trim($clean);
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status): string
    {
        return match (strtolower($status)) {
            'new', 'unread'   => 'badge badge--info',
            'confirmed'        => 'badge badge--accent',
            'completed', 'read'=> 'badge badge--success',
            'cancelled', 'no_show' => 'badge badge--muted',
            default            => 'badge badge--muted',
        };
    }
}

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        return match (strtolower($status)) {
            'new'       => 'New',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show'   => 'No show',
            'unread'    => 'Unread',
            'read'      => 'Read',
            default     => ucfirst($status),
        };
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $out   = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $out .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $out === '' ? '?' : $out;
    }
}

if (!function_exists('is_active_nav')) {
    /** True when $current sits at or below $path in the navigation tree. */
    function is_active_nav(string $path, string $current): bool
    {
        $path    = rtrim($path, '/') ?: '/';
        $current = rtrim($current, '/') ?: '/';

        if ($path === '/') {
            return $current === '/';
        }

        return $current === $path || str_starts_with($current, $path . '/');
    }
}

if (!function_exists('active_nav')) {
    function active_nav(string $path, string $current): string
    {
        return is_active_nav($path, $current) ? ' nav__link--active' : '';
    }
}

if (!function_exists('minutes_to_label')) {
    function minutes_to_label(string $time): string
    {
        $ts = strtotime('2000-01-01 ' . $time);
        if ($ts === false) {
            return $time;
        }

        return date('g:i A', $ts);
    }
}

if (!function_exists('icon_paths')) {
    /**
     * Stroke-based 24x24 icon paths. Inlined as SVG so the strict CSP
     * (script-src 'self', style-src 'self') needs no exceptions and there is
     * no extra network request.
     *
     * @return array<string,string>
     */
    function icon_paths(): array
    {
        static $paths = null;

        if ($paths !== null) {
            return $paths;
        }

        $paths = [
            'stethoscope' => '<path d="M6 3v5a4 4 0 0 0 8 0V3"/><path d="M6 3H4.5M14 3h1.5"/><path d="M10 12v3a5 5 0 0 0 5 5h.5"/><circle cx="17" cy="18" r="2.2"/><path d="M10 5h0"/>',
            'leaf'        => '<path d="M4 20C3 12 8 5 20 4c1 12-6 17-14 16Z"/><path d="M4 20C8 16 12 12 20 4"/>',
            'shield'      => '<path d="M12 3l7 3v6c0 4.4-3 8-7 9-4-1-7-4.6-7-9V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
            'clock'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
            'users'       => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c.6-3 2.9-4.6 5.5-4.6s4.9 1.6 5.5 4.6"/><path d="M16 5.4a3.2 3.2 0 0 1 0 6.1"/><path d="M17 14.8c2 .6 3.3 2.1 3.8 4.2"/>',
            'flask'       => '<path d="M9 3h6"/><path d="M10 3v6.2L4.8 18a2 2 0 0 0 1.7 3h11a2 2 0 0 0 1.7-3L14 9.2V3"/><path d="M7.4 14h9.2"/>',
            'heart'       => '<path d="M12 20s-7-4.4-7-9.2A4 4 0 0 1 12 8a4 4 0 0 1 7 2.8C19 15.6 12 20 12 20Z"/>',
            'child'       => '<circle cx="12" cy="6" r="2.6"/><path d="M8.5 21v-4.5A3.5 3.5 0 0 1 12 13a3.5 3.5 0 0 1 3.5 3.5V21"/><path d="M6 12h12"/>',
            'refresh'     => '<path d="M20 11a8 8 0 1 0-.6 4"/><path d="M20 4.5V11h-6.5"/>',
            'activity'    => '<path d="M3 12h4l2.5-7 4 14L16 12h5"/>',
            'moon'        => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5Z"/>',
            'phone'       => '<path d="M6.5 3.5h3l1.5 4-2 1.4a11 11 0 0 0 6.1 6.1l1.4-2 4 1.5v3a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.5 5.7a2 2 0 0 1 2-2.2Z"/>',
            'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2.4"/><path d="m3.6 6.6 8.4 6 8.4-6"/>',
            'pin'         => '<path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
            'calendar'    => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.2"/><path d="M3.5 9.5h17"/><path d="M8 3.5V6M16 3.5V6"/><path d="M7.5 13h3v3h-3z" fill="currentColor" stroke="none" opacity=".25"/>',
            'chat'        => '<path d="M20.5 12c0 4.1-3.8 7.4-8.5 7.4-1 0-2-.1-2.9-.4L4 20.5l1.5-3.9A7 7 0 0 1 3.5 12C3.5 7.9 7.3 4.6 12 4.6s8.5 3.3 8.5 7.4Z"/>',
            'check'       => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
            'check-circle'=> '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12.2 2.4 2.4 4.6-4.9"/>',
            'alert'       => '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4.2"/><circle cx="12" cy="17.2" r=".9" fill="currentColor" stroke="none"/>',
            'info'        => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5"/><circle cx="12" cy="7.9" r=".9" fill="currentColor" stroke="none"/>',
            'building'    => '<path d="M4 21V5.5A1.5 1.5 0 0 1 5.5 4h7A1.5 1.5 0 0 1 14 5.5V21"/><path d="M14 10h4.5A1.5 1.5 0 0 1 20 11.5V21"/><path d="M2.5 21h19"/><path d="M7 8h4M7 12h4M7 16h4M17 14h1M17 18h1"/>',
            'certificate' => '<circle cx="12" cy="9" r="5.2"/><path d="m8.6 13.4-1 7 4.4-2.2 4.4 2.2-1-7"/><path d="m10.4 9 1.1 1.1 2.1-2.2"/>',
            'note'        => '<path d="M6 3.5h9L19.5 8v12.5H6z"/><path d="M14.5 3.5V8h5"/><path d="M9 12.5h6M9 16h4"/>',
            'arrow-right' => '<path d="M5 12h13"/><path d="m13 7 5 5-5 5"/>',
            'arrow-left'  => '<path d="M19 12H6"/><path d="m11 7-5 5 5 5"/>',
            'chevron'     => '<path d="m7 10 5 5 5-5"/>',
            'close'       => '<path d="m6 6 12 12M18 6 6 18"/>',
            'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'search'      => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/>',
            'star'        => '<path d="m12 4 2.5 5.3 5.5.8-4 4 1 5.7-5-2.8-5 2.8 1-5.7-4-4 5.5-.8Z"/>',
            'quote'       => '<path d="M9 6C6.5 7.5 5 10 5 13.5A3.5 3.5 0 0 0 8.5 17c1.9 0 3.3-1.3 3.3-3.2 0-1.8-1.3-3-3-3-.4 0-.8 0-1 .2.3-1.5 1.3-2.7 2.9-3.5Z"/><path d="M18 6c-2.5 1.5-4 4-4 7.5a3.5 3.5 0 0 0 3.5 3.5c1.9 0 3.3-1.3 3.3-3.2 0-1.8-1.3-3-3-3-.4 0-.8 0-1 .2.3-1.5 1.3-2.7 2.9-3.5Z"/>',
            'whatsapp'    => '<path d="M12 3.6A8.3 8.3 0 0 0 5.2 16L3.7 20.4 8.2 19a8.3 8.3 0 1 0 3.8-15.4Z"/><path d="M9.2 8.4c.3-.1.6 0 .8.4l.7 1.3c.1.3 0 .5-.2.7l-.5.5c.6 1.1 1.6 2 2.7 2.5l.5-.6c.2-.2.4-.2.6-.1l1.3.6c.4.2.5.5.3.9-.3.6-1.1.9-1.8.8-2-.3-4.4-2.6-4.7-4.5-.1-.6.2-1.3.9-1.5Z"/>',
            'globe'       => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.2 2.4 3.4 5.4 3.4 8.5S14.2 18.1 12 20.5c-2.2-2.4-3.4-5.4-3.4-8.5S9.8 5.9 12 3.5Z"/>',
            'facebook'    => '<path d="M14.5 8.5h2.2V5.6h-2.4c-2.2 0-3.6 1.4-3.6 3.7v1.6H8.4v2.9h2.3v7.7h3V13.8h2.4l.4-2.9h-2.8V9.6c0-.7.3-1.1 1.3-1.1Z" fill="currentColor" stroke="none"/>',
            'instagram'   => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.6"/><circle cx="12" cy="12" r="4"/><circle cx="16.9" cy="7.1" r="1" fill="currentColor" stroke="none"/>',
            'x'           => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" fill="currentColor" stroke="none"/>',
            'youtube'     => '<rect x="2.6" y="5.4" width="18.8" height="13.2" rx="4"/><path d="M10.4 9.5v5l4.6-2.5-4.6-2.5Z" fill="currentColor" stroke="none"/>',
            'link'        => '<path d="M10.2 13.8a3.6 3.6 0 0 0 5.1 0l2.9-2.9a3.6 3.6 0 0 0-5.1-5.1l-1.3 1.3"/><path d="M13.8 10.2a3.6 3.6 0 0 0-5.1 0l-2.9 2.9a3.6 3.6 0 0 0 5.1 5.1l1.3-1.3"/>',
            'clock-open'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l2.6 1.6"/><path d="M17 3.5 19 5.5"/>',
            'logout'      => '<path d="M14 4.5H6.5A1.5 1.5 0 0 0 5 6v12a1.5 1.5 0 0 0 1.5 1.5H14"/><path d="M17 8.5 20.5 12 17 15.5"/><path d="M20 12H9.5"/>',
            'inbox'       => '<path d="M3.5 13.5 6 5.5h12l2.5 8v5a1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5Z"/><path d="M3.5 13.5H9a3 3 0 0 0 6 0h5.5"/>',
            'list'        => '<path d="M8 6.5h12M8 12h12M8 17.5h12"/><circle cx="4.3" cy="6.5" r="1.1" fill="currentColor" stroke="none"/><circle cx="4.3" cy="12" r="1.1" fill="currentColor" stroke="none"/><circle cx="4.3" cy="17.5" r="1.1" fill="currentColor" stroke="none"/>',
            'plus'        => '<path d="M12 5.5v13M5.5 12h13"/>',
            'edit'        => '<path d="M4.5 19.5h4L19 9a2.1 2.1 0 0 0-3-3L5.5 16.5Z"/><path d="M14.5 6.5 17.5 9.5"/>',
            'trash'       => '<path d="M4.5 7h15"/><path d="M9 7V5.2A1.2 1.2 0 0 1 10.2 4h3.6A1.2 1.2 0 0 1 15 5.2V7"/><path d="M6.5 7v12.3A1.7 1.7 0 0 0 8.2 21h7.6a1.7 1.7 0 0 0 1.7-1.7V7"/><path d="M10.5 11v6M13.5 11v6"/>',
            'eye'         => '<path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.9"/>',
            'lock'        => '<rect x="4.5" y="10" width="15" height="10.5" rx="2.2"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>',
            'download'    => '<path d="M12 4v10"/><path d="m8 10.5 4 4 4-4"/><path d="M4.5 19.5h15"/>',
            'chart'       => '<path d="M4.5 19.5h15"/><path d="M7.5 19.5V12M12 19.5V6.5M16.5 19.5v-5"/>',
            'bank'        => '<path d="m12 3.5 8 3.5H4Z"/><path d="M6 8v8M10 8v8M14 8v8M18 8v8"/><path d="M3.5 19.5h17"/>',
        ];

        return $paths;
    }
}

if (!function_exists('icon')) {
    /**
     * Render an inline SVG icon.
     */
    function icon(string $name, string $class = 'icon', string $size = '24'): string
    {
        $paths = icon_paths();
        $body  = $paths[$name] ?? $paths['info'];

        return '<svg class="' . e_attr($class) . '" width="' . e_attr($size) . '" height="' . e_attr($size) . '" '
            . 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . $body . '</svg>';
    }
}

if (!function_exists('stars')) {
    /** Rating stars for testimonials. */
    function stars(int $rating): string
    {
        $out = '<span class="stars" role="img" aria-label="' . e_attr($rating . ' out of 5 stars') . '">';

        for ($i = 1; $i <= 5; $i++) {
            $filled = $i <= $rating;
            $out   .= '<svg class="star' . ($filled ? ' star--on' : '') . '" width="16" height="16" viewBox="0 0 24 24" '
                . 'fill="' . ($filled ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.4" '
                . 'stroke-linejoin="round" aria-hidden="true">'
                . icon_paths()['star'] . '</svg>';
        }

        return $out . '</span>';
    }
}

if (!function_exists('breadcrumb_trail')) {
    /**
     * @param array<int,array{name:string,url?:string}> $trail
     * @return array<int,array{name:string,url:string,isLast:bool}>
     */
    function breadcrumb_trail(array $trail): array
    {
        $total = count($trail);
        $out   = [];

        foreach (array_values($trail) as $index => $item) {
            $out[] = [
                'name'   => (string) $item['name'],
                'url'    => (string) ($item['url'] ?? ''),
                'isLast' => $index === $total - 1,
            ];
        }

        return $out;
    }
}

