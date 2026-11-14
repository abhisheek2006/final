<?php
/**
 * Privacy policy.
 *
 * @var array<string,mixed> $clinic
 * @var array<int,array{title:string,body:string}> $privacySections
 * @var string $updated
 */
?>

<?= component('page-hero', [
    'eyebrow'   => 'Legal',
    'heading'   => 'Privacy Policy',
    'lead'      => 'How we collect, use, store and protect the health information you send us through this '
        . 'website.',
    'trail'     => [['name' => 'Home', 'url' => '/'], ['name' => 'Privacy Policy']],
    'variant'   => 'slate',
]) ?>

<section class="section">
    <div class="container container--reading">
        <p class="legal-meta">
            Last updated: <strong><?= e($updated) ?></strong> · Applies to <?= e(url('/')) ?>
        </p>

        <div class="prose prose--legal">
            <p>
                This policy explains how <?= e((string) $clinic['name']) ?> handles information submitted
                through the consultation, follow-up and contact forms on this website.
            </p>

            <?php foreach ($privacySections as $index => $section): ?>
                <h3 id="privacy-<?= e($index + 1) ?>"><?= e($section['title']) ?></h3>
                <p><?= e($section['body']) ?></p>
            <?php endforeach; ?>
        </div>

        <div class="legal-contact">
            <h2>Questions about this policy</h2>
            <p>
                Write to us at <a href="<?= e(mail_link()) ?>"><?= e((string) $clinic['email']) ?></a>
                or call <?= e(format_phone()) ?>. For anything about your own health information held by the
                clinic, please ask to speak to the doctor directly.
            </p>
            <p class="legal-contact__meta">
                <?= icon('lock', 'icon icon--xs', 15) ?>
                We never sell, rent or trade patient information. We do not run advertising or tracking cookies.
            </p>
        </div>
    </div>
</section>
