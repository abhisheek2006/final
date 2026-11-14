<?php
/**
 * Public layout.
 *
 * @var string $content
 * @var string $page
 * @var array<string,mixed> $seo
 * @var array<string,mixed> $clinic
 * @var array<int,array{label:string,url:string,match:string}> $primaryNav
 * @var array<string,array<int,array{label:string,url:string}>> $footerNav
 * @var string|null $flashSuccess
 * @var string|null $flashError
 * @var int $appYear
 */

$currentPath = rtrim((string) ($_SERVER['REQUEST_URI'] ?? '/'), '/');
$currentPath = strtok($currentPath, '?') ?: '/';
$currentPath = '/' . trim($currentPath, '/');
$currentPath = $currentPath === '/' ? '/' : rtrim($currentPath, '/');
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($seo['title'] ?? 'Swasti Homoeo Clinic') ?></title>
<meta name="description" content="<?= e($seo['description'] ?? '') ?>">
<meta name="robots" content="<?= e($seo['robots'] ?? 'index, follow') ?>">
<meta name="author" content="Swasti Homoeo Clinic, Sri Vijaya Puram">
<meta name="theme-color" content="#0f6f68">
<link rel="canonical" href="<?= e($seo['canonical'] ?? url('/')) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&amp;display=swap">

<meta property="og:type" content="<?= e($seo['type'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= e($seo['site_name'] ?? 'Swasti Homoeo Clinic') ?>">
<meta property="og:locale" content="<?= e($seo['locale'] ?? 'en_IN') ?>">
<meta property="og:title" content="<?= e($seo['title'] ?? '') ?>">
<meta property="og:description" content="<?= e($seo['description'] ?? '') ?>">
<meta property="og:url" content="<?= e($seo['canonical'] ?? url('/')) ?>">
<meta property="og:image" content="<?= e($seo['image'] ?? url('/assets/images/og/og-default.png')) ?>">
<meta property="og:image:alt" content="Swasti Homoeo Clinic — homoeopathic clinic in Sri Vijaya Puram">
<?php if (!empty($seo['published_time'])): ?>
<meta property="article:published_time" content="<?= e($seo['published_time']) ?>">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo['title'] ?? '') ?>">
<meta name="twitter:description" content="<?= e($seo['description'] ?? '') ?>">
<meta name="twitter:image" content="<?= e($seo['image'] ?? url('/assets/images/og/og-default.png')) ?>">
<?php if (!empty($seo['twitter_site'])): ?>
<meta name="twitter:site" content="<?= e($seo['twitter_site']) ?>">
<?php endif; ?>

<link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
<link rel="icon" type="image/png" sizes="192x192" href="<?= e(url('/assets/images/icons/icon-192.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('/assets/images/icons/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">

<script src="<?= e(asset('/assets/js/maintenance-gate.js')) ?>"></script>
<link rel="stylesheet" href="<?= e(asset('/assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/footer.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/responsive.css')) ?>">
<?= !empty($jsonLd) ? json_ld($jsonLd) : '' ?>
<?= $sections['head'] ?? '' ?>
</head>
<body class="page page--<?= e($page ?? 'default') ?>">

<?= component('header', ['clinic' => $clinic, 'primaryNav' => $primaryNav, 'currentPath' => $currentPath]) ?>

<main id="main" class="main">
<?php if (!empty($flashSuccess) || !empty($flashError)): ?>
    <div class="container flash-wrap">
        <div class="flash-stack" data-flash-stack>
            <?php if (!empty($flashSuccess)): ?>
            <div class="flash flash--success" role="status" data-flash>
                <span class="flash__icon"><?= icon('check-circle', 'icon icon--sm', 20) ?></span>
                <p class="flash__text"><?= e($flashSuccess) ?></p>
                <button type="button" class="flash__close" data-flash-close aria-label="Dismiss message"><?= icon('close', 'icon icon--xs', 16) ?></button>
            </div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
            <div class="flash flash--error" role="alert" data-flash>
                <span class="flash__icon"><?= icon('alert', 'icon icon--sm', 20) ?></span>
                <p class="flash__text"><?= e($flashError) ?></p>
                <button type="button" class="flash__close" data-flash-close aria-label="Dismiss message"><?= icon('close', 'icon icon--xs', 16) ?></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?= $content ?>
</main>

<?= component('footer', ['clinic' => $clinic, 'footerNav' => $footerNav, 'appYear' => $appYear]) ?>

<script src="<?= e(asset('/assets/js/navigation.js')) ?>" defer></script>
<script src="<?= e(asset('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
