<?php
/** @var array{enabled:bool,message:string,updated_at:string} $settings */
/** @var string $storageError */
?>
<div class="admin-head">
    <div>
        <p class="admin-eyebrow">Website settings</p>
        <h1 class="admin-head__title">Maintenance mode</h1>
        <p class="admin-head__sub">Temporarily replace the public website with a notice. The admin area remains available.</p>
    </div>
</div>

<?php if ($storageError !== ''): ?>
    <div class="flash flash--error" role="alert"><p class="flash__text"><?= e($storageError) ?></p></div>
<?php endif; ?>

<div class="admin-columns admin-columns--form">
    <section class="admin-card">
        <header class="admin-card__head">
            <h2 class="admin-card__title">Public website</h2>
            <span class="badge <?= $settings['enabled'] ? 'badge--warn' : 'badge--success' ?>">
                <?= $settings['enabled'] ? 'Maintenance on' : 'Website live' ?>
            </span>
        </header>

        <form class="form" method="post" action="<?= e(url('/admin/maintenance')) ?>">
            <?= csrf_field() ?>

            <fieldset class="field field--check">
                <legend class="field__label">Maintenance mode</legend>
                <input type="hidden" name="maintenance_enabled" value="0">
                <label class="check">
                    <input type="checkbox" name="maintenance_enabled" value="1"<?= $settings['enabled'] ? ' checked' : '' ?>>
                    <span>Turn on maintenance mode for visitors</span>
                </label>
                <p class="field__hint">When enabled, visitors see the notice below and appointment/contact submissions are paused. You can still use the admin area.</p>
            </fieldset>

            <div class="field">
                <label class="field__label" for="maintenance_message">Visitor message</label>
                <textarea class="field__input field__input--area" id="maintenance_message" name="maintenance_message" rows="4" maxlength="400"><?= e($settings['message']) ?></textarea>
                <p class="field__hint">Up to 400 characters. Leave blank to use the default notice.</p>
            </div>

            <div class="form-actions">
                <button class="btn btn--primary" type="submit"><?= icon('check', 'icon icon--xs', 15) ?> Save settings</button>
            </div>
        </form>
    </section>

    <aside class="admin-card admin-card--side">
        <header class="admin-card__head"><h2 class="admin-card__title">What visitors will see</h2></header>
        <p class="muted">A clear maintenance screen replaces the public page while this setting is on. It will use your visitor message and provide a link to call the clinic.</p>
        <p class="muted">The status is stored in MongoDB. If the database is unavailable, the website stays live and the setting cannot be changed.</p>
    </aside>
</div>
