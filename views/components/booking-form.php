<?php
/**
 * Appointment form — shared by /book-consultation and /book-follow-up.
 *
 * @var array<string,string> $clinic
 * @var array<int,string> $timeSlots
 * @var array<int,string> $reasons
 * @var array<int,string> $ages
 * @var array<string,mixed> $doctor
 * @var bool $isFollowUp
 * @var string|null $formId
 */
$isFollowUp = $isFollowUp ?? false;
$formId     = $formId ?? ($isFollowUp ? 'followup-form' : 'consultation-form');

// The booking modal embeds this same partial on pages that already contain a
// form (the contact page), so the two sets of ids would collide. Only the modal
// is namespaced; the standalone booking pages keep their original ids.
$prefix = in_array($formId, ['consultation-form', 'followup-form'], true) ? '' : 'bm-';
?>
<form class="form form--booking"
      id="<?= e($formId) ?>"
      method="post"
      action="<?= e(api_url('/api/booking.php')) ?>"
      novalidate
      data-ajax-form
      data-csrf-url="<?= e(api_url('/api/csrf.php')) ?>"
      data-form-name="<?= $isFollowUp ? 'follow-up' : 'consultation' ?>">

    <?= csrf_field() ?>
    <input type="hidden" name="booking_type" value="<?= $isFollowUp ? 'follow_up' : 'consultation' ?>">
    <?= component('form-traps', ['prefix' => $prefix]) ?>
    <p class="form__status" data-form-status role="status" aria-live="polite"></p>

    <div class="form__grid">
        <div class="field field--full<?= has_error('full_name') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>full_name">Full name <span class="field__req" aria-hidden="true">*</span></label>
            <input class="field__input" type="text" id="<?= $prefix ?>full_name" name="full_name" required
                   autocomplete="name" maxlength="120" inputmode="text"
                   value="<?= old('full_name') ?>"
                   aria-describedby="full_name-help<?= has_error('full_name') ? ' full_name-err' : '' ?>"
                   <?= has_error('full_name') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__help" id="<?= $prefix ?>full_name-help">As it appears on your ID or prescription.</p>
            <?php if (has_error('full_name')): ?>
                <p class="field__error" id="<?= $prefix ?>full_name-err"><?= e(error_for('full_name')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field<?= has_error('phone') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>phone">Phone number <span class="field__req" aria-hidden="true">*</span></label>
            <input class="field__input" type="tel" id="<?= $prefix ?>phone" name="phone" required
                   autocomplete="tel" maxlength="25" inputmode="tel" placeholder="10-digit mobile number"
                   value="<?= old('phone') ?>"
                   aria-describedby="phone-help<?= has_error('phone') ? ' phone-err' : '' ?>"
                   <?= has_error('phone') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__help" id="<?= $prefix ?>phone-help">We call or WhatsApp this number to confirm your slot.</p>
            <?php if (has_error('phone')): ?>
                <p class="field__error" id="<?= $prefix ?>phone-err"><?= e(error_for('phone')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field<?= has_error('age') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>age">Age <span class="field__req" aria-hidden="true">*</span></label>
            <input class="field__input" type="number" id="<?= $prefix ?>age" name="age" required
                   min="0" max="130" step="1" inputmode="numeric" placeholder="Years"
                   value="<?= old('age') ?>"
                   list="age-presets"
                   aria-describedby="age-help<?= has_error('age') ? ' age-err' : '' ?>"
                   <?= has_error('age') ? 'aria-invalid="true"' : '' ?>>
            <datalist id="<?= $prefix ?>age-presets">
                <option value="1"></option><option value="5"></option><option value="10"></option>
                <option value="18"></option><option value="35"></option><option value="55"></option>
                <option value="70"></option>
            </datalist>
            <p class="field__help" id="<?= $prefix ?>age-help">In years. Helps us choose a safe dose and potency.</p>
            <?php if (has_error('age')): ?>
                <p class="field__error" id="<?= $prefix ?>age-err"><?= e(error_for('age')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field<?= has_error('email') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>email">Email <span class="field__optional">optional</span></label>
            <input class="field__input" type="email" id="<?= $prefix ?>email" name="email"
                   autocomplete="email" maxlength="190" placeholder="you@example.com"
                   value="<?= old('email') ?>"
                   aria-describedby="email-help<?= has_error('email') ? ' email-err' : '' ?>"
                   <?= has_error('email') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__help" id="<?= $prefix ?>email-help">Only if you want the confirmation in writing.</p>
            <?php if (has_error('email')): ?>
                <p class="field__error" id="<?= $prefix ?>email-err"><?= e(error_for('email')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field<?= has_error('preferred_date') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>preferred_date">Preferred date <span class="field__req" aria-hidden="true">*</span></label>
            <input class="field__input" type="date" id="<?= $prefix ?>preferred_date" name="preferred_date" required
                   min="<?= e($minDate ?? date('Y-m-d')) ?>"
                   max="<?= e($maxDate ?? date('Y-m-d', strtotime('+60 days'))) ?>"
                   value="<?= old('preferred_date') ?>"
                   data-no-sundays data-sunday-message="The clinic is closed on Sundays. Choose a Monday–Saturday date."
                   aria-describedby="preferred_date-help<?= has_error('preferred_date') ? ' preferred_date-err' : '' ?>"
                   <?= has_error('preferred_date') ? 'aria-invalid="true"' : '' ?>>
            <p class="field__help" id="<?= $prefix ?>preferred_date-help">Monday to Saturday OPD. We confirm the exact time.</p>
            <?php if (has_error('preferred_date')): ?>
                <p class="field__error" id="<?= $prefix ?>preferred_date-err"><?= e(error_for('preferred_date')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field field--full<?= has_error('preferred_time') ? ' field--error' : '' ?>">
            <span class="field__label" id="<?= $prefix ?>slot-label">Preferred time slot <span class="field__req" aria-hidden="true">*</span></span>
            <div class="chips" role="radiogroup" aria-labelledby="<?= $prefix ?>slot-label" data-chip-group="preferred_time">
                <?php
                $selectedSlot = (string) old('preferred_time', $timeSlots[0] ?? '');
                foreach ($timeSlots as $slot): ?>
                    <label class="chip">
                        <input type="radio" name="preferred_time" value="<?= e($slot) ?>"
                               data-chip-input required
                               <?= $selectedSlot === $slot ? 'checked' : '' ?>>
                        <span class="chip__text"><?= e(minutes_to_label($slot)) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="field__help"><?= $isFollowUp ? 'Short review slot.' : 'OPD: Monday–Saturday, 9:00 AM–12:30 PM and 4:30–7:30 PM. Closed Sunday.' ?></p>
            <?php if (has_error('preferred_time')): ?>
                <p class="field__error"><?= e(error_for('preferred_time')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field field--full<?= has_error('reason') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>reason-category">What is the main problem? <span class="field__req" aria-hidden="true">*</span></label>
            <?php $oldReason = old('reason'); $isCustomReason = $oldReason !== '' && !in_array($oldReason, $reasons, true); ?>
            <select class="field__input field__input--problem" id="<?= $prefix ?>reason-category"
                    data-own-words-select data-reason-hidden="<?= $prefix ?>reason-value"
                    data-reason-details="<?= $prefix ?>reason-details" required>
                <option value="">Choose a problem</option>
                <?php foreach ($reasons as $reason): ?>
                    <option value="<?= e($reason) ?>" <?= $oldReason === $reason ? 'selected' : '' ?>><?= e($reason) ?></option>
                <?php endforeach; ?>
                <option value="__custom__" <?= $isCustomReason ? 'selected' : '' ?>>In your own words</option>
            </select>
            <input type="hidden" id="<?= $prefix ?>reason-value" name="reason"
                   value="<?= e(in_array($oldReason, $reasons, true) ? $oldReason : '') ?>"
                   <?= $isCustomReason ? 'disabled' : '' ?>>
            <div class="field field--full" id="<?= $prefix ?>reason-details-wrap"<?= $isCustomReason ? '' : ' hidden' ?>>
                <label class="field__label field__label--sub" for="<?= $prefix ?>reason-details">In your own words <span class="field__req" aria-hidden="true">*</span></label>
                <textarea class="field__input field__textarea" id="<?= $prefix ?>reason-details" name="reason" rows="4"
                          <?= $isCustomReason ? 'required' : 'disabled' ?> maxlength="1500"
                          placeholder="Tell us what is troubling you..." data-counter="<?= $prefix ?>reason-counter"
                          aria-describedby="<?= $prefix ?>reason-help <?= $prefix ?>reason-counter<?= has_error('reason') ? ' ' . $prefix . 'reason-err' : '' ?>"
                          <?= has_error('reason') ? 'aria-invalid="true"' : '' ?>><?= e($isCustomReason ? $oldReason : '') ?></textarea>
                <p class="field__meta">
                    <span class="field__help" id="<?= $prefix ?>reason-help">Where is it, how long has it been there, what makes it better or worse?</span>
                    <span class="field__count" id="<?= $prefix ?>reason-counter" data-count-for="<?= $prefix ?>reason-details">0 / 1500</span>
                </p>
            </div>
            <?php if (has_error('reason')): ?>
                <p class="field__error" id="<?= $prefix ?>reason-err"><?= e(error_for('reason')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field field--full<?= has_error('message') ? ' field--error' : '' ?>">
            <label class="field__label" for="<?= $prefix ?>message">Anything else we should know? <span class="field__optional">optional</span></label>
            <textarea class="field__input field__textarea" id="<?= $prefix ?>message" name="message" rows="3"
                      maxlength="4000" data-counter="<?= $prefix ?>message-counter"
                      aria-describedby="message-help message-counter<?= has_error('message') ? ' message-err' : '' ?>"><?= old('message') ?></textarea>
            <p class="field__meta">
                <span class="field__help" id="<?= $prefix ?>message-help">Current medicines, past treatments, allergies, pregnancy or breastfeeding.</span>
                <span class="field__count" id="<?= $prefix ?>message-counter" data-count-for="<?= $prefix ?>message">0 / 4000</span>
            </p>
            <?php if (has_error('message')): ?>
                <p class="field__error" id="<?= $prefix ?>message-err"><?= e(error_for('message')) ?></p>
            <?php endif; ?>
        </div>

        <div class="field field--full<?= has_error('consent') ? ' field--error' : '' ?>">
            <label class="checkbox">
                <input type="checkbox" name="consent" value="1" required
                       <?= old('consent') === '1' ? 'checked' : '' ?>
                       aria-describedby="<?= $prefix ?>consent-text<?= has_error('consent') ? ' ' . $prefix . 'consent-err' : '' ?>"
                       <?= has_error('consent') ? 'aria-invalid="true"' : '' ?>>
                <span class="checkbox__box" aria-hidden="true"><?= icon('check', 'icon icon--xs', 13) ?></span>
                <span class="checkbox__text" id="<?= $prefix ?>consent-text">
                    I consent to <?= e($clinic['name'] ?? 'Swasti Homoeo Clinic') ?> contacting me by phone or WhatsApp
                    about this appointment, and I understand this form is not for emergencies.
                    <span class="field__req" aria-hidden="true">*</span>
                </span>
            </label>
            <?php if (has_error('consent')): ?>
                <p class="field__error" id="<?= $prefix ?>consent-err"><?= e(error_for('consent')) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form__footer">
        <p class="form__note">
            <?= icon('lock', 'icon icon--xs', 15) ?>
            Your details are used only to arrange and prepare for your visit. Read our
            <a href="<?= e(url('/privacy-policy')) ?>">privacy policy</a>.
        </p>
        <button class="btn btn--primary btn--lg" type="submit" data-submit-button>
            <span data-submit-label><?= $isFollowUp ? 'Request follow-up review' : 'Request consultation' ?></span>
            <span class="btn__spinner" data-submit-spinner hidden aria-hidden="true"></span>
        </button>
    </div>
</form>
