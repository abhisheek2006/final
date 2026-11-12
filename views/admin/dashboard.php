<?php
/**
 * Admin dashboard.
 *
 * @var array<string,mixed> $clinic
 * @var bool $dbOk
 * @var string $dbError
 * @var array<string,int> $statusCounts
 * @var array<string,int> $messageCounts
 * @var array<int,array<string,mixed>> $recent
 * @var array<string,int> $fallback
 * @var array<int,array{file:string,records:int,size:int,modified:int}> $inventory
 * @var bool $mailReady
 * @var string $mailReason
 * @var bool $mongoConfigured
 * @var bool $mongoDriverAvailable
 * @var bool $appKeySet
 * @var bool $debug
 */

$totalAppointments = array_sum($statusCounts);
$unread           = (int) ($messageCounts['unread'] ?? 0);
?>

<div class="admin-head">
    <div>
        <h1 class="admin-head__title">Dashboard</h1>
        <p class="admin-head__sub">Appointment requests, messages and system health.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/appointments')) ?>">
            <?= icon('calendar', 'icon icon--xs', 15) ?> All appointments
        </a>
        <a class="btn btn--primary btn--sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
            <?= icon('globe', 'icon icon--xs', 15) ?> View website
        </a>
    </div>
</div>

<?php if (!$dbOk): ?>
<div class="flash flash--error" role="alert">
    <span class="flash__icon"><?= icon('alert', 'icon icon--sm', 20) ?></span>
    <div class="flash__body">
        <p class="flash__text"><strong>MongoDB is not available.</strong> New appointments and messages are
        saved to <code>storage/submissions/</code> until MongoDB connects. They appear in the admin lists;
        record edits require MongoDB.</p>
        <?php if ($dbError !== ''): ?>
            <p class="flash__meta">Driver message: <?= e(truncate($dbError, 160)) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!$mailReady): ?>
<div class="flash flash--warning" role="alert">
    <span class="flash__icon"><?= icon('mail', 'icon icon--sm', 20) ?></span>
    <div class="flash__body">
        <p class="flash__text"><strong>SMTP is not configured.</strong> Enquiries are still stored, but no
        notification emails are being sent.</p>
        <p class="flash__meta"><?= e($mailReason !== '' ? $mailReason : 'Set MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in .env') ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (!$appKeySet): ?>
<div class="flash flash--warning" role="alert">
    <span class="flash__icon"><?= icon('lock', 'icon icon--sm', 20) ?></span>
    <div class="flash__body">
        <p class="flash__text">Set <code>APP_KEY</code> in <code>.env</code> to a long random string.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($debug): ?>
<div class="flash flash--warning" role="alert">
    <span class="flash__icon"><?= icon('alert', 'icon icon--sm', 20) ?></span>
    <p class="flash__text"><strong>Debug mode is on.</strong> Set <code>APP_DEBUG=false</code> and
    <code>APP_ENV=production</code> on the live host.</p>
</div>
<?php endif; ?>

<div class="stat-cards">
    <a class="stat-card<?= (int) ($statusCounts['new'] ?? 0) > 0 ? ' stat-card--alert' : '' ?>" href="<?= e(url('/admin/appointments?status=new')) ?>">
        <span class="stat-card__label">New requests</span>
        <span class="stat-card__value"><?= e((string) ($statusCounts['new'] ?? 0)) ?></span>
        <span class="stat-card__meta">Awaiting confirmation</span>
    </a>
    <a class="stat-card" href="<?= e(url('/admin/appointments?status=confirmed')) ?>">
        <span class="stat-card__label">Confirmed</span>
        <span class="stat-card__value"><?= e((string) ($statusCounts['confirmed'] ?? 0)) ?></span>
        <span class="stat-card__meta">Booked for a slot</span>
    </a>
    <a class="stat-card" href="<?= e(url('/admin/appointments?status=completed')) ?>">
        <span class="stat-card__label">Completed</span>
        <span class="stat-card__value"><?= e((string) ($statusCounts['completed'] ?? 0)) ?></span>
        <span class="stat-card__meta">All-time appointments <?= e((string) $totalAppointments) ?></span>
    </a>
    <a class="stat-card<?= $unread > 0 ? ' stat-card--alert' : '' ?>" href="<?= e(url('/admin/messages')) ?>">
        <span class="stat-card__label">Unread messages</span>
        <span class="stat-card__value"><?= e((string) $unread) ?></span>
        <span class="stat-card__meta"><?= e((string) ($messageCounts['total'] ?? 0)) ?> in total</span>
    </a>
</div>

<div class="admin-columns">
    <section class="admin-card">
        <header class="admin-card__head">
            <h2 class="admin-card__title">Latest appointment requests</h2>
            <a class="admin-card__link" href="<?= e(url('/admin/appointments')) ?>">View all</a>
        </header>

        <?php if ($recent === []): ?>
            <div class="empty">
                <?= icon('inbox', 'icon', 28) ?>
                <p class="empty__text">
                    No appointment requests have come in yet.
                </p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <caption class="visually-hidden">Latest appointment requests</caption>
                <thead>
                    <tr>
                        <th scope="col">Patient</th>
                        <th scope="col">Type</th>
                        <th scope="col">Preferred slot</th>
                        <th scope="col">Status</th>
                        <th scope="col">Received</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                    <tr>
                        <td>
                            <span class="cell-strong"><?= e((string) $row['full_name']) ?></span>
                            <span class="cell-meta"><?= e((string) $row['reference_code']) ?></span>
                        </td>
                        <td><?= e($row['type'] === 'follow_up' ? 'Follow-up' : 'Consultation') ?></td>
                        <td>
                            <?= e(format_date((string) $row['preferred_date'])) ?>
                            <span class="cell-meta"><?= e(minutes_to_label((string) $row['preferred_time'])) ?></span>
                        </td>
                        <td><span class="<?= e(status_badge((string) $row['status'])) ?>"><?= e(status_label((string) $row['status'])) ?></span></td>
                        <td><span class="cell-meta"><?= e(time_ago((string) $row['created_at'])) ?></span></td>
                        <td class="table__actions">
                            <a class="btn btn--ghost btn--xs" href="<?= e(url('/admin/appointments?q=' . rawurlencode((string) $row['reference_code']))) ?>">Open</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <header class="admin-card__head">
            <h2 class="admin-card__title">System health</h2>
        </header>

        <ul class="check-list check-list--status">
            <li>
                <?= icon($dbOk ? 'check-circle' : 'alert', 'icon icon--sm', 18) ?>
                <span><strong>MongoDB</strong>
                    <?= $dbOk ? 'connected' : (!$mongoConfigured ? 'URI not configured' : (!$mongoDriverAvailable ? 'PHP mongodb extension is required' : 'unavailable — using file fallback')) ?>
                </span>
            </li>
            <li>
                <?= icon($mailReady ? 'check-circle' : 'alert', 'icon icon--sm', 18) ?>
                <span><strong>SMTP</strong> <?= $mailReady ? 'configured' : 'not configured' ?></span>
            </li>
            <li>
                <?= icon($appKeySet ? 'check-circle' : 'alert', 'icon icon--sm', 18) ?>
                <span><strong>APP_KEY</strong> <?= $appKeySet ? 'set' : 'missing' ?></span>
            </li>
            <li>
                <?= icon($debug ? 'alert' : 'check-circle', 'icon icon--sm', 18) ?>
                <span><strong>Debug mode</strong> <?= $debug ? 'ON — turn this off in production' : 'off' ?></span>
            </li>
            <li>
                <?= icon('check-circle', 'icon icon--sm', 18) ?>
                <span><strong>Session</strong> hardened (HttpOnly, SameSite, idle timeout)</span>
            </li>
        </ul>

        <div class="admin-card__section">
            <h3 class="admin-card__subtitle">Fallback submissions</h3>
            <?php if ($inventory === []): ?>
                <p class="muted">
                    No fallback files. Nothing has needed the file store — counts:
                    <?= e((string) ($fallback['appointments'] ?? 0)) ?> appointments,
                    <?= e((string) ($fallback['messages'] ?? 0)) ?> messages.
                </p>
            <?php else: ?>
                <p class="muted">
                    These records were written to <code>storage/submissions/</code> because MongoDB was
                    unavailable. Import them once MongoDB is working.
                </p>
                <ul class="file-list">
                    <?php foreach ($inventory as $file): ?>
                    <li>
                        <code><?= e($file['file']) ?></code>
                        <span class="cell-meta">
                            <?= e((string) $file['records']) ?> record<?= $file['records'] === 1 ? '' : 's' ?>,
                            <?= e(number_format($file['size'] / 1024, 1)) ?> KB,
                            <?= e(date('j M Y H:i', $file['modified'])) ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="admin-card__section">
            <h3 class="admin-card__subtitle">Quick actions</h3>
            <div class="button-row">
                <a class="btn btn--outline btn--xs" href="<?= e(url('/admin/faqs')) ?>"><?= icon('list', 'icon icon--xs', 14) ?> Manage FAQs</a>
                <a class="btn btn--outline btn--xs" href="<?= e(url('/admin/messages')) ?>"><?= icon('inbox', 'icon icon--xs', 14) ?> Read messages</a>
                <a class="btn btn--outline btn--xs" href="<?= e(url('/sitemap.xml')) ?>" target="_blank" rel="noopener"><?= icon('globe', 'icon icon--xs', 14) ?> sitemap.xml</a>
                <a class="btn btn--outline btn--xs" href="<?= e(url('/health')) ?>" target="_blank" rel="noopener"><?= icon('activity', 'icon icon--xs', 14) ?> Health check</a>
            </div>
        </div>
    </section>
</div>
