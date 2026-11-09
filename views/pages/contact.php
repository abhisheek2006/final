<?php
/**
 * Contact page: details, hours, directions and the contact form.
 *
 * @var array<string,mixed> $clinic
 * @var array<string,array{periods:array<int,array{open:string,close:string}>,closed?:bool,note?:string}> $hours
 * @var array<int,string> $addressLines
 */
$dayLabels = [
    'monday'    => 'Monday',
    'tuesday'   => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday'  => 'Thursday',
    'friday'    => 'Friday',
    'saturday'  => 'Saturday',
    'sunday'    => 'Sunday',
];
$clinicNow  = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
$nowMinutes = (int) $clinicNow->format('G') * 60 + (int) $clinicNow->format('i');
$todayKey   = strtolower($clinicNow->format('l'));
$isToday    = static function (string $day) use ($todayKey): bool {
    return $day === $todayKey;
};
?>

<?= component('page-hero', [
    'eyebrow'   => 'Contact',
    'heading'   => 'Contact & Directions',
    'lead'      => 'Call, WhatsApp or send a message. We are in Garacharma, Sri Vijaya Puram, '
        . 'and we answer every message ourselves.',
    'trail'     => [['name' => 'Home', 'url' => '/'], ['name' => 'Contact']],
    'variant'   => 'blue',
]) ?>

<section class="section">
    <div class="container contact-grid">
        <div class="contact-grid__main">
            <div class="contact-cards">
                <a class="contact-card" href="<?= e(tel_link()) ?>" data-track="contact-card-call">
                    <span class="contact-card__icon"><?= icon('phone', 'icon', 24) ?></span>
                    <span class="contact-card__body">
                        <span class="contact-card__label">Call the clinic</span>
                        <span class="contact-card__value"><?= e(format_phone()) ?></span>
                        <span class="contact-card__hint">OPD Mon–Sat, 9 AM–12:30 PM and 4:30–7:30 PM. Closed Sunday.</span>
                    </span>
                </a>

                <a class="contact-card" href="<?= e(whatsapp_link('Hello, I would like to ask about homoeopathic treatment.')) ?>" rel="noopener nofollow" target="_blank" data-track="contact-card-whatsapp">
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

                <a class="contact-card" href="<?= e(url('/book-consultation')) ?>">
                    <span class="contact-card__icon"><?= icon('calendar', 'icon', 24) ?></span>
                    <span class="contact-card__body">
                        <span class="contact-card__label">Book online</span>
                        <span class="contact-card__value">Request an appointment</span>
                        <span class="contact-card__hint">We confirm your slot by phone or WhatsApp.</span>
                    </span>
                </a>
            </div>

            <div class="panel" id="contact-form">
                <h2 class="panel__title">Send us a message</h2>
                <p class="panel__lead">
                    Use this form for a general question, to share what you would like treated, or to ask about
                    an existing prescription. For anything urgent, call instead — this inbox is read during
                    clinic hours.
                </p>

                <form class="form form--contact" method="post" action="<?= e(api_url('/api/contact.php')) ?>" novalidate
                      data-ajax-form data-csrf-url="<?= e(api_url('/api/csrf.php')) ?>" data-form-name="contact">
                    <?= csrf_field() ?>
                    <?= component('form-traps') ?>
                    <p class="form__status" data-form-status role="status" aria-live="polite"></p>

                    <div class="form__grid">
                        <div class="field<?= has_error('name') ? ' field--error' : '' ?>">
                            <label class="field__label" for="name">Your name <span class="field__req" aria-hidden="true">*</span></label>
                            <input class="field__input" type="text" id="name" name="name" required
                                   autocomplete="name" maxlength="120" value="<?= old('name') ?>"
                                   <?= has_error('name') ? 'aria-invalid="true"' : '' ?>>
                            <?php if (has_error('name')): ?>
                                <p class="field__error"><?= e(error_for('name')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field<?= has_error('phone') ? ' field--error' : '' ?>">
                            <label class="field__label" for="phone">Phone number <span class="field__req" aria-hidden="true">*</span></label>
                            <input class="field__input" type="tel" id="phone" name="phone" required
                                   autocomplete="tel" maxlength="25" inputmode="tel" placeholder="10-digit mobile number"
                                   value="<?= old('phone') ?>"
                                   <?= has_error('phone') ? 'aria-invalid="true"' : '' ?>>
                            <?php if (has_error('phone')): ?>
                                <p class="field__error"><?= e(error_for('phone')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field<?= has_error('email') ? ' field--error' : '' ?>">
                            <label class="field__label" for="email">Email <span class="field__optional">optional</span></label>
                            <input class="field__input" type="email" id="email" name="email"
                                   autocomplete="email" maxlength="190" placeholder="you@example.com"
                                   value="<?= old('email') ?>"
                                   <?= has_error('email') ? 'aria-invalid="true"' : '' ?>>
                            <?php if (has_error('email')): ?>
                                <p class="field__error"><?= e(error_for('email')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field<?= has_error('subject') ? ' field--error' : '' ?>">
                            <label class="field__label" for="subject">Subject <span class="field__req" aria-hidden="true">*</span></label>
                            <select class="field__input" id="subject" name="subject" required
                                    <?= has_error('subject') ? 'aria-invalid="true"' : '' ?>>
                                <option value="">Choose one…</option>
                                <?php
                                $subjects = [
                                    'Appointment request',
                                    'Existing prescription review',
                                    'Fee and scheduling enquiry',
                                    'Report or prescription question',
                                    'Directions to the clinic',
                                    'Something else',
                                ];
                                $oldSubject = (string) old('subject');
                                foreach ($subjects as $subject): ?>
                                    <option value="<?= e($subject) ?>" <?= $oldSubject === $subject ? 'selected' : '' ?>>
                                        <?= e($subject) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (has_error('subject')): ?>
                                <p class="field__error"><?= e(error_for('subject')) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="field field--full<?= has_error('message') ? ' field--error' : '' ?>">
                            <label class="field__label" for="message">Your message <span class="field__req" aria-hidden="true">*</span></label>
                            <textarea class="field__input field__textarea" id="message" name="message" rows="6"
                                      required minlength="10" maxlength="4000" data-counter="message-counter"
                                      aria-describedby="message-help message-counter"
                                      <?= has_error('message') ? 'aria-invalid="true"' : '' ?>><?= old('message') ?></textarea>
                            <p class="field__meta">
                                <span class="field__help" id="message-help">Please do not send photographs or highly sensitive details here — share those during your visit.</span>
                                <span class="field__count" id="message-counter" data-count-for="message">0 / 4000</span>
                            </p>
                            <?php if (has_error('message')): ?>
                                <p class="field__error"><?= e(error_for('message')) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form__footer">
                        <p class="form__note">
                            <?= icon('lock', 'icon icon--xs', 15) ?>
                            Read only by the clinic team. See our <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a>.
                        </p>
                        <button class="btn btn--primary btn--lg" type="submit" data-submit-button>
                            <span data-submit-label>Send message</span>
                            <span class="btn__spinner" data-submit-spinner hidden aria-hidden="true"></span>
                        </button>
                    </div>
                    <p class="form__status" role="status" aria-live="polite" data-form-status></p>
                </form>
            </div>
        </div>

        <aside class="contact-grid__aside">
            <div class="panel panel--tint">
                <h2 class="panel__title">Address</h2>
                <address class="address">
                    <?php foreach ($addressLines as $line): ?>
                        <span class="address__line"><?= e($line) ?></span>
                    <?php endforeach; ?>
                </address>
                <p class="panel__note">
                    <?= icon('pin', 'icon icon--xs', 15) ?>
                    Near Susane roses on NH4 main road, Garacharma.
                </p>
                <p class="panel__actions">
                    <a class="btn btn--outline btn--sm" target="_blank" rel="noopener nofollow"
                       href="<?= e(\App\Content\Clinic::mapUrl()) ?>">
                        <?= icon('pin', 'icon icon--xs', 15) ?> Open in Google Maps
                    </a>
                </p>
            </div>

            <div class="panel">
                <h2 class="panel__title">Opening hours</h2>
                <table class="hours" data-hours-table>
                    <caption class="visually-hidden">Weekly opening hours</caption>
                    <tbody>
                    <?php foreach ($hours as $day => $slot): ?>
                        <?php
                        $isOpen = false;
                        $periodLabels = [];
                        $nextOpening = null;
                        foreach ($slot['periods'] as $period) {
                            $openM = ((int) substr($period['open'], 0, 2)) * 60 + (int) substr($period['open'], 3, 2);
                            $closeM = ((int) substr($period['close'], 0, 2)) * 60 + (int) substr($period['close'], 3, 2);
                            $isOpen = $isOpen || ($nowMinutes >= $openM && $nowMinutes < $closeM);
                            if ($openM > $nowMinutes && ($nextOpening === null || $openM < $nextOpening)) {
                                $nextOpening = $openM;
                            }
                            $periodLabels[] = minutes_to_label($period['open']) . ' – ' . minutes_to_label($period['close']);
                        }
                        ?>
                        <tr class="hours__row<?= $isToday($day) ? ' hours__row--today' : '' ?>" data-hours-day="<?= e(strtolower((string) $day)) ?>">
                            <th scope="row">
                                <?= e($dayLabels[$day] ?? ucfirst((string) $day)) ?>
                                <?php if ($isToday($day)): ?><span class="hours__today-label">Today</span><?php endif; ?>
                            </th>
                            <td>
                                <?php if (!empty($slot['closed'])): ?>
                                    <span class="hours__closed">Closed</span>
                                <?php else: ?>
                                    <?= e(implode(' · ', $periodLabels)) ?>
                                <?php endif; ?>
                            </td>
                            <td class="hours__state">
                                <?php if ($isToday($day)): ?>
                                    <?php if ($isOpen): ?>
                                        <span class="badge badge--success">Open now</span>
                                    <?php elseif (!empty($slot['closed'])): ?>
                                        <span class="badge badge--muted">Closed</span>
                                    <?php elseif ($nextOpening !== null): ?>
                                        <?php $nextOpeningTime = sprintf('%02d:%02d', intdiv($nextOpening, 60), $nextOpening % 60); ?>
                                        <span class="badge badge--muted">Opens at <?= e(minutes_to_label($nextOpeningTime)) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge--muted">Closed for today</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="panel__note">
                    Patients are given a specific slot, so waiting time is short. If you are running a fever on
                    the day of your appointment, please call and tell us before travelling.
                </p>
            </div>
        </aside>
    </div>
</section>

