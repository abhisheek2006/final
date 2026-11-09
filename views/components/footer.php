<?php
/**
 * Site footer — brand and clinic details over a layered botanical wave.
 *
 * Column 1 brand, tagline and socials. Column 2 clinic address, phone, email
 * and the directions button. Opening hours and the quick-links list were
 * removed at the client's request; the header and sitemap still cover them.
 *
 * Every link, address and social URL comes from Clinic / .env, so nothing here
 * is hardcoded twice. Social networks without a configured URL fall back to
 * href="#", so an icon never disappears — add the URL to .env and the real
 * link goes live.
 *
 * @var array<string,string> $clinic
 * @var array<string,array<int,array{label:string,url:string}>> $footerNav
 * @var int $appYear
 */
use App\Content\Clinic;

$socials = [
    ['label' => 'Facebook',  'icon' => 'facebook',  'url' => (string) ($clinic['facebook'] ?? '')],
    ['label' => 'Instagram', 'icon' => 'instagram', 'url' => (string) ($clinic['instagram'] ?? '')],
    ['label' => 'YouTube',   'icon' => 'youtube',   'url' => (string) ($clinic['youtube'] ?? '')],
    ['label' => 'Location',  'icon' => 'pin',       'url' => Clinic::mapUrl(), 'external' => true],
];

/* The email opens a Gmail compose window rather than the visitor's own mail
   client. Gmail's web composer is the URL below; on a phone the same link
   hands off to the Gmail app. Change CLINIC_EMAIL in .env and both the visible
   address and the compose link follow. */
$email = (string) ($clinic['email'] ?? '');
$emailCompose = $email !== ''
    ? 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($email)
        . '&su=' . rawurlencode('Enquiry — ' . Clinic::NAME)
    : '';
?>
<footer class="site-footer">
    <div class="site-footer__wave" aria-hidden="true"></div>

    <span class="site-footer__leaf site-footer__leaf--tl" aria-hidden="true"></span>
    <span class="site-footer__leaf site-footer__leaf--bl" aria-hidden="true"></span>
    <span class="site-footer__leaf site-footer__leaf--br" aria-hidden="true"></span>
    <span class="site-footer__leaf site-footer__leaf--tr" aria-hidden="true"></span>
    <span class="site-footer__sprig" aria-hidden="true"></span>

    <div class="site-footer__main">
        <div class="site-footer__container">
            <div class="site-footer__grid">

                <div class="site-footer__brand">
                    <a class="site-footer__logo-link" href="<?= e(url('/')) ?>" aria-label="Swasti Homoeo Clinic — home">
                        <img class="site-footer__logo"
                             src="<?= e(url('/assets/images/icons/logo.png')) ?>"
                             width="280" height="99"
                             alt="Swasti Homoeo Clinic">
                    </a>

                    <p class="site-footer__tagline"><?= e((string) ($clinic['tagline'] ?? Clinic::TAGLINE)) ?></p>
                    <span class="site-footer__rule" aria-hidden="true"></span>

                    <p class="site-footer__about">
                        A classical homoeopathy practice in Garacharma, Sri Vijaya Puram, led by
                        Dr.&nbsp;Smriti Das (BHMS, MD&nbsp;— WBUHS). Your health, our sincere care.
                    </p>

                    <ul class="site-footer__socials">
                        <?php foreach ($socials as $social): ?>
                            <?php
                            $href     = $social['url'] !== '' ? $social['url'] : '#';
                            $external = ($social['external'] ?? false) && $social['url'] !== '';
                            ?>
                            <li>
                                <a class="site-footer__social"
                                   href="<?= e($href) ?>"
                                   aria-label="<?= e($social['label']) ?>"
                                   <?= $external ? 'target="_blank" rel="noopener nofollow"' : '' ?>>
                                    <?= icon($social['icon'], 'site-footer__social-icon', 24) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="site-footer__col site-footer__clinic">
                    <h2 class="site-footer__heading">
                        <?= icon('pin', 'site-footer__heading-icon', 20) ?>Visit the clinic
                    </h2>

                    <address class="site-footer__address">
                        <?php foreach ((array) ($clinic['address'] ?? Clinic::addressLines()) as $line): ?>
                            <span class="site-footer__address-line"><?= e((string) $line) ?></span>
                        <?php endforeach; ?>
                    </address>

                    <ul class="site-footer__contact">
                        <li>
                            <a class="site-footer__contact-link" href="<?= e(tel_link()) ?>">
                                <?= icon('phone', 'site-footer__contact-icon', 18) ?>
                                <span><?= e(format_phone()) ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="site-footer__contact-link"
                               href="<?= e($emailCompose) ?>"
                               target="_blank" rel="noopener noreferrer"
                               aria-label="Email <?= e($clinic['name'] ?? Clinic::NAME) ?> on Gmail">
                                <?= icon('mail', 'site-footer__contact-icon', 18) ?>
                                <span><?= e($email) ?></span>
                            </a>
                        </li>
                    </ul>

                    <a class="site-footer__directions"
                       href="<?= e(Clinic::mapUrl()) ?>"
                       target="_blank" rel="noopener nofollow">
                        <?= icon('pin', 'site-footer__directions-icon', 18) ?>
                        <span>Get Directions</span>
                        <?= icon('arrow-right', 'site-footer__directions-arrow', 18) ?>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <div class="site-footer__rule-full" aria-hidden="true"></div>

    <div class="site-footer__bottom">
        <div class="site-footer__container site-footer__bottom-inner">
            <p class="site-footer__copy">
                &copy; <?= e((string) $appYear) ?> Swasti Homoeo Clinic &middot;
                <?= e(Clinic::LEGAL_NAME) ?>. All rights reserved.
            </p>

            <ul class="site-footer__legal">
                <li><a class="site-footer__legal-link" href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a></li>
                <li><a class="site-footer__legal-link" href="<?= e(url('/sitemap.xml')) ?>">Sitemap</a></li>
                <li><a class="site-footer__legal-link" href="<?= e(url('/faq')) ?>">FAQ</a></li>
            </ul>
        </div>
    </div>
</footer>
