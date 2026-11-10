<?php
/**
 * About Us.
 *
 * @var array<string,mixed> $clinic
 * @var array<string,mixed> $doctor
 * @var array<int,array{heading:string,body:string}> $biography
 * @var array<int,array{title:string,text:string,icon:string}> $credentials
 * @var array<int,string> $languages
 * @var array<int,array<string,mixed>> $testimonials
 */
?>

<?= component('page-hero', [
    'eyebrow'   => 'About us',
    'heading'   => 'A small clinic that takes its prescribing seriously',
    'lead'      => \App\Content\Clinic::intro(),
    'trail'     => [['name' => 'Home', 'url' => '/'], ['name' => 'About Us']],
]) ?>

<section class="section" id="doctor">
    <div class="container split">
        <div class="split__media" data-reveal>
            <div class="split__frame split__frame--portrait">
                <img src="<?= e(image_url((string) $doctor['image'])) ?>"
                     alt="<?= e((string) $doctor['image_alt']) ?>"
                     width="700" height="1050" loading="lazy" decoding="async">
            </div>
            <div class="split__badge">
                <p class="split__badge-value"><?= e((string) $doctor['experience']) ?></p>
                <p class="split__badge-label"><?= e((string) $doctor['role']) ?></p>
            </div>
        </div>

        <div class="split__text" data-reveal>
            <p class="eyebrow">The doctor</p>
            <h2 class="section__title"><?= e((string) $doctor['name']) ?></h2>
            <p class="section__lead"><?= e((string) $doctor['short_qualification']) ?></p>

            <ul class="tag-list">
                <?php foreach ((array) $doctor['qualifications'] as $qualification): ?>
                    <li class="tag"><?= icon('certificate', 'icon icon--xs', 14) ?> <?= e((string) $qualification) ?></li>
                <?php endforeach; ?>
            </ul>

            <div class="prose">
                <?php foreach ($biography as $block): ?>
                    <?php if ($block['heading'] !== ''): ?>
                    <h3><?= e($block['heading']) ?></h3>
                    <?php endif; ?>
                    <p><?= e($block['body']) ?></p>
                <?php endforeach; ?>
            </div>

            <div class="detail-grid">
                <div class="detail">
                    <p class="detail__label">Registration</p>
                    <p class="detail__value"><?= e((string) $doctor['registration_note']) ?></p>
                </div>
                <div class="detail">
                    <p class="detail__label">Languages</p>
                    <p class="detail__value"><?= e(implode(' · ', $languages)) ?></p>
                </div>
            </div>

            <div class="split__actions">
                <a class="btn btn--primary" href="<?= e(url('/book-consultation')) ?>">Book with Dr. Das</a>
                <a class="btn btn--ghost" href="<?= e(tel_link()) ?>">Call <?= e(format_phone()) ?></a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <header class="section__head" data-reveal>
            <p class="eyebrow">Credentials</p>
            <h2 class="section__title">Registered, licensed and accountable</h2>
        </header>

        <div class="cards cards--four">
            <?php foreach ($credentials as $credential): ?>
            <article class="card card--small" data-reveal>
                <span class="card__icon card__icon--soft"><?= icon($credential['icon'], 'icon', 22) ?></span>
                <h3 class="card__title card__title--sm"><?= e($credential['title']) ?></h3>
                <p class="card__text"><?= e($credential['text']) ?></p>
            </article>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<section class="section section--testimonials" id="testimonials">
    <div class="container">
        <header class="section__head" data-reveal>
            <p class="eyebrow">In their words</p>
            <h2 class="section__title">What patients tell us</h2>
            <p class="section__lead section__lead--center">
                Real feedback matters more than marketing, so this is exactly what we are told — including the
                parts that are not flattering.
            </p>
        </header>

        <div class="testimonials">
            <div class="testimonials__track" data-testimonials>
                <?php foreach ($testimonials as $testimonial): ?>
                <figure class="testimonial" data-reveal>
                    <?= icon('quote', 'testimonial__quote-mark', 30) ?>
                    <blockquote class="testimonial__quote"><?= e($testimonial['quote']) ?></blockquote>
                    <figcaption class="testimonial__meta">
                        <?php $avatar = (string) ($testimonial['avatar'] ?? ''); ?>
                        <?php if (has_image($avatar)): ?>
                        <img class="testimonial__avatar" src="<?= e(asset($avatar)) ?>" width="108" height="108" loading="lazy" decoding="async" alt="<?= e($testimonial['name']) ?>">
                        <?php else: ?>
                        <span class="testimonial__avatar testimonial__avatar--initials" aria-hidden="true"><?= e(initials((string) $testimonial['name'])) ?></span>
                        <?php endif; ?>
                        <span>
                            <span class="testimonial__name"><?= e($testimonial['name']) ?></span>
                            <span class="testimonial__meta-line">Verified patient<?= ($testimonial['when'] ?? '') !== '' ? ' · ' . e((string) $testimonial['when']) : '' ?></span>
                        </span>
                        <?= stars((int) $testimonial['rating']) ?>
                    </figcaption>
                </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

