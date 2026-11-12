<?php
/**
 * Swasti Homoeo Clinic — application configuration.
 *
 * Values are resolved from environment variables (see .env / .env.example).
 * Nothing in this file contains a secret. Database credentials, mail
 * credentials and the admin password hash all come from the .env file.
 *
 * @return array<string,mixed>
 */

declare(strict_types=1);

return [
    'app' => [
        'name'      => 'Swasti Homoeo Clinic',
        'legal'     => 'Andaman Homoeo Health Care LLP',
        'legal_form'=> 'Limited Liability Partnership',
        'env'       => 'production',
        'debug'     => false,
        'url'       => 'https://swastihomoeo.com',
        'timezone'  => 'Asia/Kolkata',
        'locale'    => 'en_IN',
        'key'       => '',
        'version'   => '1.0.0',
        'tagline'   => 'We believe in easy, safe and quick recovery',
    ],

    /*
     * Base URL of the PHP form backend.
     *
     * Leave empty when the site and the API share a domain (Hostinger). Set
     * API_BASE_URL to the PHP host, e.g. https://swastihomoeo.com, when the
     * static pages are served from Vercel and the forms post back to the PHP
     * backend cross-origin.
     */
    'api' => [
        'base_url' => '',
    ],

    'paths' => [
        // 'root' / 'public' are auto-detected at runtime by App\Core\Paths.
        'root'      => null,
        'public'    => null,
    ],

    'mail' => [
        'enabled'     => false,   // becomes true as soon as MAIL_HOST is set
        'transport'   => 'smtp',  // smtp
        'host'        => '',
        'port'        => 587,
        'username'    => '',
        'password'    => '',
        'encryption'  => 'tls',   // tls | ssl | none
        'timeout'     => 15,
        'from_address'=> 'info@swastihomeo.com',
        'from_name'   => 'Swasti Homoeo Clinic',
        'reply_to'    => 'info@swastihomeo.com',
        'to_address'  => 'info@swastihomeo.com',
        'cc'          => '',
    ],

    'admin' => [
        'name'     => 'Clinic Administrator',
        'email'    => 'swastihomoeo24@gmail.com',
        'password_hash' => '',
        'session_name'  => 'swasti_admin',
        'max_attempts'  => 6,
        'lockout_seconds' => 900,
        'idle_timeout'  => 3600,
    ],

    'clinic' => [
        'phone'     => '+917980644867',
        'whatsapp'  => '917980644867',
        'email'     => 'info@swastihomeo.com',
        'facebook'  => '',
        'instagram' => '',
        'x'         => '',
        'youtube'   => '',
    ],

    'security' => [
        'csrf_token_name'      => '_token',
        'csrf_field'           => '_token',
        'session_name'         => 'swasti_session',
        'session_lifetime'     => 7200,
        'cookie_secure'        => null,  // null => auto (true when HTTPS is active)
        'cookie_samesite'      => 'Lax',
        'cookie_path'          => '/',
        'cookie_domain'        => '',
        'admin_idle_timeout'   => 3600,
        'force_https'          => true,
        'hsts_max_age'         => 15552000, // 180 days
        'trusted_proxies'      => [],
    ],

    'forms' => [
        'min_dwell_seconds'   => 3,
        'max_dwell_seconds'   => 86400,
        'max_name_length'     => 120,
        'max_email_length'    => 190,
        'max_phone_length'    => 25,
        'max_message_length'  => 4000,
        'max_reason_length'   => 1500,
        'max_links_allowed'   => 1,
        'rate_limits'         => [
            'consultation' => ['max' => 5,  'window' => 3600],
            'follow_up'    => ['max' => 5,  'window' => 3600],
            'contact'      => ['max' => 8,  'window' => 3600],
            'login'        => ['max' => 6,  'window' => 900],
        ],
    ],

    'seo' => [
        'title_suffix' => 'Swasti Homoeo Clinic',
        'default_image'=> '/assets/images/og/og-default.png',
        'twitter_site' => '',
        'index_admin'  => false,
    ],
];
