<?php
/**
 * Admin layout.
 *
 * @var string $content
 * @var string $page
 * @var array<string,mixed> $seo
 * @var array<string,mixed> $clinic
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */

$pageTitles = [
    'dashboard'    => 'Dashboard',
    'appointments' => 'Appointments',
    'messages'     => 'Messages',
    'faqs'         => 'FAQ management',
    'maintenance'  => 'Maintenance mode',
];
$pageTitle  = (string) ($pageTitles[$page ?? ''] ?? 'Admin');
$authUser   = \App\Models\User::user() ?? [];

$navSections = [
    'Overview' => [
        ['label' => 'Dashboard', 'url' => '/admin', 'icon' => 'chart'],
    ],
    'Enquiries' => [
        ['label' => 'Appointments', 'url' => '/admin/appointments', 'icon' => 'calendar'],
        ['label' => 'Messages',     'url' => '/admin/messages',     'icon' => 'inbox'],
    ],
    'Content' => [
        ['label' => 'FAQs', 'url' => '/admin/faqs', 'icon' => 'list'],
    ],
    'Website' => [
        ['label' => 'Maintenance mode', 'url' => '/admin/maintenance', 'icon' => 'clock'],
    ],
];
$isActive = static function (string $url, string $page): bool {
    $current = (string) ($page ?? '');
    if ($url === '/admin') {
        return $current === 'dashboard';
    }

    return $current === trim($url, '/');
};
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo['title'] ?? 'Admin') ?> · <?= e((string) ($clinic['name'] ?? 'Swasti Homoeo Clinic')) ?></title>
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="same-origin">
<meta name="theme-color" content="#0d2a5e">
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
<link rel="icon" type="image/png" href="<?= e(url('/assets/images/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/main.css')) ?>">
<?= !empty($jsonLd) ? json_ld($jsonLd) : '' ?>
</head>
<body class="admin-body" data-admin>

<a class="skip-link" href="#admin-main">Skip to content</a>

<aside class="admin-sidebar" data-sidebar>
    <div class="admin-sidebar__head">
        <a class="brand brand--admin" href="<?= e(url('/admin')) ?>">
            <img class="brand__mark" src="<?= e(url('/assets/images/icons/logo.png')) ?>" width="34" height="34" alt="" aria-hidden="true">
            <span class="brand__text">
                <span class="brand__name">Clinic Admin</span>
                <span class="brand__tag"><?= e((string) ($clinic['name'] ?? '')) ?></span>
            </span>
        </a>
    </div>

    <nav class="admin-nav" aria-label="Admin navigation">
        <?php foreach ($navSections as $heading => $links): ?>
            <p class="admin-nav__heading"><?= e($heading) ?></p>
            <ul class="admin-nav__list">
                <?php foreach ($links as $link): ?>
                    <li>
                        <a class="admin-nav__link<?= $isActive($link['url'], (string) ($page ?? '')) ? ' is-active' : '' ?>"
                           href="<?= e(url($link['url'])) ?>"
                           <?= $isActive($link['url'], (string) ($page ?? '')) ? 'aria-current="page"' : '' ?>>
                            <?= icon($link['icon'], 'icon icon--sm', 18) ?>
                            <span><?= e($link['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar__foot">
        <a class="admin-nav__link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
            <?= icon('globe', 'icon icon--sm', 18) ?> <span>View website</span>
        </a>
        <form method="post" action="<?= e(url('/admin/logout')) ?>" class="admin-logout">
            <?= csrf_field() ?>
            <button class="admin-nav__link admin-nav__link--button" type="submit" data-confirm="Sign out of the admin area?">
                <?= icon('logout', 'icon icon--sm', 18) ?> <span>Sign out</span>
            </button>
        </form>
    </div>
</aside>

<div class="admin-shell">
    <header class="admin-topbar">
        <button class="admin-burger" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="admin-main" aria-label="Toggle menu">
            <?= icon('menu', 'icon icon--sm', 20) ?>
        </button>
        <p class="admin-topbar__title"><?= e($pageTitle) ?></p>
        <div class="admin-topbar__right">
            <span class="admin-user" title="<?= e((string) ($authUser['email'] ?? '')) ?>">
                <span class="admin-user__avatar" aria-hidden="true"><?= e(initials((string) ($authUser['name'] ?? 'Admin'))) ?></span>
                <span class="admin-user__name"><?= e((string) ($authUser['name'] ?? 'Administrator')) ?></span>
            </span>
        </div>
    </header>

    <main class="admin-main" id="admin-main">
        <?php if (!empty($flashSuccess) || !empty($flashError)): ?>
            <div class="flash-stack">
                <?php if (!empty($flashSuccess)): ?>
                <div class="flash flash--success" role="status" data-flash>
                    <span class="flash__icon"><?= icon('check-circle', 'icon icon--sm', 20) ?></span>
                    <p class="flash__text"><?= e($flashSuccess) ?></p>
                    <button type="button" class="flash__close" data-flash-close aria-label="Dismiss"><?= icon('close', 'icon icon--xs', 16) ?></button>
                </div>
                <?php endif; ?>
                <?php if (!empty($flashError)): ?>
                <div class="flash flash--error" role="alert" data-flash>
                    <span class="flash__icon"><?= icon('alert', 'icon icon--sm', 20) ?></span>
                    <p class="flash__text"><?= e($flashError) ?></p>
                    <button type="button" class="flash__close" data-flash-close aria-label="Dismiss"><?= icon('close', 'icon icon--xs', 16) ?></button>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>

<script src="<?= e(asset('/assets/js/admin.js')) ?>" defer></script>
</body>
</html>
