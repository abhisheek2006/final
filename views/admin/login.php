<?php
/**
 * Admin sign-in.
 *
 * @var array<string,mixed> $clinic
 * @var array<string,string> $old
 * @var array<string,string> $errors
 * @var bool $locked
 * @var int $attempts
 */
$old    = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
?>

<div class="auth-card">
    <div class="auth-card__head">
        <img class="auth-card__logo" src="/assets/images/icons/logo.png" width="52" height="52" alt="">
        <h1 class="auth-card__title">Clinic admin sign in</h1>
        <p class="auth-card__sub"><?= e((string) ($clinic['name'] ?? '')) ?> · Sri Vijaya Puram</p>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="flash flash--success" role="status">
            <span class="flash__icon"><?= icon('check-circle', 'icon icon--sm', 20) ?></span>
            <p class="flash__text"><?= e($flashSuccess) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="flash flash--error" role="alert">
            <span class="flash__icon"><?= icon('alert', 'icon icon--sm', 20) ?></span>
            <p class="flash__text"><?= e($flashError) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($locked): ?>
        <div class="flash flash--error" role="alert">
            <span class="flash__icon"><?= icon('lock', 'icon icon--sm', 20) ?></span>
            <p class="flash__text">
                Too many failed attempts (<?= e((string) $attempts) ?>). Sign-in is locked for 15 minutes.
            </p>
        </div>
    <?php endif; ?>

    <form class="form form--auth" method="post" action="/admin/login" novalidate>
        <?= csrf_field() ?>

        <div class="field<?= isset($errors['email']) ? ' field--error' : '' ?>">
            <label class="field__label" for="email">Email address</label>
            <input class="field__input" type="email" id="email" name="email" required
                   autocomplete="username" maxlength="190" inputmode="email"
                   value="<?= e($old['email'] ?? '') ?>"
                   <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['email'])): ?>
                <p class="field__error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field<?= isset($errors['password']) ? ' field--error' : '' ?>">
            <label class="field__label" for="password">Password</label>
            <input class="field__input" type="password" id="password" name="password" required
                   autocomplete="current-password" maxlength="200"
                   <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['password'])): ?>
                <p class="field__error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <button class="btn btn--primary btn--lg btn--block" type="submit"<?= $locked ? ' disabled' : '' ?>>
            <?= icon('lock', 'icon icon--xs', 16) ?> Sign in
        </button>

        <p class="auth-card__note">
            Credentials come from <code>ADMIN_EMAIL</code> and <code>ADMIN_PASSWORD_HASH</code> in
            <code>.env</code>, or from the <code>users</code> table. Repeated failures lock sign-in for
            15 minutes.
        </p>
    </form>
</div>
