<?php
/**
 * Shared booking page body. Included by pages/book-consultation.php and
 * pages/book-follow-up.php with $isFollowUp already set.
 *
 * @var bool $isFollowUp
 * @var string $heading
 * @var string $intro
 * @var array<string,mixed> $clinic
 * @var array<string,mixed> $doctor
 * @var array<int,string> $timeSlots
 * @var array<int,string> $reasons
 * @var array<int,string> $ages
 * @var array<int,string> $preparation
 * @var array<int,string> $afterBooking
 */
$slug = $isFollowUp ? 'book-follow-up' : 'book-consultation';

$formTitle = $isFollowUp ? 'Request a follow-up review' : 'Request your appointment';
$formLead  = $isFollowUp
    ? 'A follow-up review takes about 15 minutes. Bring your current remedy bottles and any notes '
        . 'from your last visit so we can compare properly.'
    : 'Tell us what you would like treated and choose a preferred slot. We confirm the exact time by '
        . 'phone or WhatsApp, usually the same day. Booking is free.';
?>

<?= component('page-hero', [
    'eyebrow'   => $isFollowUp ? 'Follow-up review' : 'Appointment request',
    'heading'   => $heading,
    'lead'      => $intro,
    'trail'     => [
        ['name' => 'Home', 'url' => '/'],
        ['name' => $isFollowUp ? 'Follow-Up' : 'Booking', 'url' => '/book-consultation'],
        ['name' => $isFollowUp ? 'Book a review' : 'Book a consultation'],
    ],
    'variant'   => $isFollowUp ? 'teal' : 'blue',
]) ?>

<section class="section">
    <div class="container booking-grid">
        <div class="booking-grid__main">
            <div class="contact-cards">
                <a class="contact-card" href="<?= e(tel_link()) ?>" data-track="booking-card-call">
                    <span class="contact-card__icon"><?= icon('phone', 'icon', 24) ?></span>
                    <span class="contact-card__body">
                        <span class="contact-card__label">Call the clinic</span>
                        <span class="contact-card__value"><?= e(format_phone()) ?></span>
                        <span class="contact-card__hint">Fastest way to book. Mon–Sat 9 AM–12:30 PM and 4:30–7:30 PM.</span>
                    </span>
                </a>

                <a class="contact-card" href="<?= e(whatsapp_link('Hello, I would like to book ' . ($isFollowUp ? 'a follow-up review' : 'a consultation') . '.')) ?>" rel="noopener nofollow" target="_blank" data-track="booking-card-whatsapp">
                    <span class="contact-card__icon contact-card__icon--whatsapp"><?= icon('whatsapp', 'icon', 24) ?></span>
                    <span class="contact-card__body">
                        <span class="contact-card__label">WhatsApp</span>
                        <span class="contact-card__value"><?= e(format_phone()) ?></span>
                        <span class="contact-card__hint">Send a photo of a report or a prescription here.</span>
                    </span>
                </a>

                <a class="contact-card" href="<?= e(mail_link('Appointment request — Swasti Homoeo Clinic')) ?>">
                    <span class="contact-card__icon"><?= icon('mail', 'icon', 24) ?></span>
                    <span class="contact-card__body">
                        <span class="contact-card__label">Email</span>
                        <span class="contact-card__value"><?= e((string) $clinic['email']) ?></span>
                        <span class="contact-card__hint">Useful for reports and detailed questions.</span>
                    </span>
                </a>

                <?php if ($isFollowUp): ?>
                    <a class="contact-card" href="<?= e(url('/book-consultation')) ?>">
                        <span class="contact-card__icon"><?= icon('stethoscope', 'icon', 24) ?></span>
                        <span class="contact-card__body">
                            <span class="contact-card__label">New consultation</span>
                            <span class="contact-card__value">First visit or a new problem</span>
                            <span class="contact-card__hint">Allow 30 to 45 minutes for a full consultation.</span>
                        </span>
                    </a>
                <?php else: ?>
                    <a class="contact-card" href="<?= e(url('/book-follow-up')) ?>">
                        <span class="contact-card__icon"><?= icon('calendar', 'icon', 24) ?></span>
                        <span class="contact-card__body">
                            <span class="contact-card__label">Follow-up review</span>
                            <span class="contact-card__value">Already under treatment here</span>
                            <span class="contact-card__hint">Shorter review of your current prescription.</span>
                        </span>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($isFollowUp): ?>
            <aside class="notice notice--info" role="note">
                <span class="notice__icon"><?= icon('refresh', 'icon', 22) ?></span>
                <div class="notice__body">
                    <p class="notice__text">
                        Already under treatment here? Book a review rather than a new consultation — it is
                        shorter, cheaper, and keeps your prescription history intact. Bring your current
                        remedy bottles.
                    </p>
                </div>
            </aside>
            <?php else: ?>
            <aside class="notice notice--info" role="note">
                <span class="notice__icon"><?= icon('stethoscope', 'icon', 22) ?></span>
                <div class="notice__body">
                    <p class="notice__text">
                        First visit? Allow 30 to 45 minutes. Bring your current medicine bottles and any
                        reports — the history is what makes the prescribing accurate.
                    </p>
                </div>
            </aside>
            <?php endif; ?>

            <div class="panel" id="<?= e($slug) ?>-form">
                <h2 class="panel__title"><?= e($formTitle) ?></h2>
                <p class="panel__lead"><?= e($formLead) ?></p>

                <?= component('booking-form', [
                    'clinic'      => $clinic,
                    'doctor'      => $doctor,
                    'timeSlots'   => $timeSlots,
                    'reasons'     => $reasons,
                    'ages'        => $ages,
                    'isFollowUp'  => $isFollowUp,
                ]) ?>
            </div>
        </div>

        <aside class="booking-grid__aside">
            <div class="panel">
                <h2 class="panel__title panel__title--sm">Before you come</h2>
                <ul class="check-list check-list--tight">
                    <?php foreach ($preparation as $tip): ?>
                        <li><?= icon('check', 'icon icon--xs', 14) ?> <?= e($tip) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel">
                <h2 class="panel__title panel__title--sm">After you submit</h2>
                <ul class="check-list check-list--tight">
                    <?php foreach ($afterBooking as $note): ?>
                        <?php if (!$isFollowUp && str_starts_with($note, 'If you are running a fever on the day,')) { continue; } ?>
                        <li><?= icon('arrow-right', 'icon icon--xs', 14) ?> <?= e($note) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel panel--tint">
                <h2 class="panel__title panel__title--sm">Clinic</h2>
                <ul class="mini-list">
                    <li><span><?= icon('stethoscope', 'icon icon--xs', 15) ?></span> <?= e((string) $doctor['name']) ?>, <?= e((string) $doctor['short_qualification']) ?></li>
                    <li><span><?= icon('pin', 'icon icon--xs', 15) ?></span> <?= e((string) $clinic['address_full']) ?></li>
                    <li><span><?= icon('phone', 'icon icon--xs', 15) ?></span> <a href="<?= e(tel_link()) ?>"><?= e(format_phone()) ?></a></li>
                    <li><span><?= icon('clock-open', 'icon icon--xs', 15) ?></span> Mon–Sat 9 AM–12:30 PM and 4:30–7:30 PM · Sun closed</li>
                    <li><span><?= icon('globe', 'icon icon--xs', 15) ?></span> <?= e(implode(', ', (array) ($doctor['languages'] ?? []))) ?></li>
                </ul>
                <p class="panel__actions">
                    <a class="btn btn--whatsapp btn--sm btn--block" href="<?= e(whatsapp_link('Hello, I would like to ' . ($isFollowUp ? 'book a follow-up review' : 'book a consultation') . '.')) ?>" rel="noopener nofollow" target="_blank">
                        <?= icon('whatsapp', 'icon icon--xs', 15) ?> Prefer WhatsApp?
                    </a>
                </p>
            </div>
        </aside>
    </div>
</section>
