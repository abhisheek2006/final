<?php
/**
 * Minimal layout for the admin sign-in screen: no navigation, no sidebar.
 *
 * @var string $content
 * @var array<string,mixed> $seo
 * @var array<string,mixed> $clinic
 * @var string|null $flashSuccess
 * @var string|null $flashError
 */
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo['title'] ?? 'Sign in') ?> · <?= e((string) ($clinic['name'] ?? 'Swasti Homoeo Clinic')) ?></title>
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="same-origin">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="/assets/images/icons/logo.png">
<link rel="stylesheet" href="<?= e(asset('/assets/css/main.css')) ?>">
</head>
<body class="auth-body">
<main class="auth-shell">
    <?= $content ?>
</main>
<footer class="auth-foot">
    <p>&copy; <?= e((string) ($appYear ?? date('Y'))) ?> <?= e((string) ($clinic['legal_name'] ?? '')) ?></p>
    <p><a href="/"><?= icon('arrow-left', 'icon icon--xs', 14) ?> Back to the website</a></p>
</footer>
</body>
</html>
