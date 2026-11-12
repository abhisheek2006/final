<?php
/**
 * Appointment requests: filter, change status, delete.
 *
 * @var array<string,mixed> $clinic
 * @var array<int,array<string,mixed>> $rows
 * @var array<string,string> $filters
 * @var array<string,int> $counts
 * @var int $total
 * @var int $perPage
 * @var int $current
 * @var int $lastPage
 * @var string[] $statuses
 * @var array<string,string> $statusLabels
 */

$filters     = is_array($filters ?? null) ? $filters : [];
$statuses    = is_array($statuses ?? null) ? $statuses : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$total       = (int) ($total ?? 0);
$current     = max(1, (int) ($current ?? 1));
$lastPage    = max(1, (int) ($lastPage ?? 1));
$perPage     = max(1, (int) ($perPage ?? 25));

/** Rebuild the current query string with one value changed. */
$withFilter = static function (string $key, string $value, int $page = 1) use ($filters): string {
    $params = $filters;
    $params[$key] = $value;
    unset($params['page']);
    $query = http_build_query(array_filter($params, static fn ($v): bool => (string) $v !== ''));

    return '/admin/appointments' . ($query === '' ? '' : '?' . $query . ($page > 1 ? '&page=' . $page : ''));
};
?>

<div class="admin-head">
    <div>
        <h1 class="admin-head__title">Appointments</h1>
        <p class="admin-head__sub">
            <?= e((string) $total) ?> request<?= $total === 1 ? '' : 's' ?> match the current filter.
            Confirming a request does not email the patient unless you tick “Notify patient”.
        </p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/appointments')) ?>"><?= icon('refresh', 'icon icon--xs', 15) ?> Reset filters</a>
    </div>
</div>

<nav class="filter-chips" aria-label="Filter by status">
    <a class="filter-chip<?= ($filters['status'] ?? '') === '' ? ' is-active' : '' ?>" href="<?= e(url($withFilter('status', ''))) ?>">
        All <span class="filter-chip__count"><?= e((string) array_sum($counts)) ?></span>
    </a>
    <?php foreach ($statuses as $status): ?>
        <a class="filter-chip<?= ($filters['status'] ?? '') === $status ? ' is-active' : '' ?>" href="<?= e(url($withFilter('status', $status))) ?>">
            <?= e($statusLabels[$status] ?? $status) ?> <span class="filter-chip__count"><?= e((string) ($counts[$status] ?? 0)) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form class="filter-bar" method="get" action="<?= e(url('/admin/appointments')) ?>">
    <div class="filter-bar__field">
        <label class="field__label" for="q">Search</label>
        <input class="field__input" type="search" id="q" name="q" maxlength="100"
               placeholder="Name, phone, email or reference" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <div class="filter-bar__field">
        <label class="field__label" for="status">Status</label>
        <select class="field__input" id="status" name="status">
            <option value="">Any status</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>>
                    <?= e($statusLabels[$status] ?? $status) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="field__label" for="type">Type</label>
        <select class="field__input" id="type" name="type">
            <option value="">Any type</option>
            <option value="consultation"<?= ($filters['type'] ?? '') === 'consultation' ? ' selected' : '' ?>>Consultation</option>
            <option value="follow_up"<?= ($filters['type'] ?? '') === 'follow_up' ? ' selected' : '' ?>>Follow-up</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="field__label" for="from">From</label>
        <input class="field__input" type="date" id="from" name="from" value="<?= e($filters['from'] ?? '') ?>">
    </div>
    <div class="filter-bar__field">
        <label class="field__label" for="to">To</label>
        <input class="field__input" type="date" id="to" name="to" value="<?= e($filters['to'] ?? '') ?>">
    </div>
    <div class="filter-bar__actions">
        <button class="btn btn--primary btn--sm" type="submit"><?= icon('search', 'icon icon--xs', 15) ?> Apply</button>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/appointments')) ?>">Clear</a>
    </div>
</form>

<?php if ($rows === []): ?>
    <div class="admin-card">
        <div class="empty">
            <?= icon('calendar', 'icon', 28) ?>
            <p class="empty__text">
                <?= $total === 0 && ($filters['q'] ?? '') === '' && ($filters['status'] ?? '') === ''
                    ? 'No appointment requests yet. New requests from the website appear here immediately.'
                    : 'No requests match these filters.' ?>
            </p>
        </div>
    </div>
<?php else: ?>

<div class="table-wrap">
    <table class="table table--cards">
        <caption class="visually-hidden">Appointment requests</caption>
        <thead>
            <tr>
                <th scope="col">Patient</th>
                <th scope="col">Contact</th>
                <th scope="col">Reason</th>
                <th scope="col">Preferred slot</th>
                <th scope="col">Received</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $id       = (int) ($row['id'] ?? 0);
                $status   = (string) ($row['status'] ?? 'new');
                $isFollow = ($row['type'] ?? '') === 'follow_up';
                $spam     = (int) ($row['spam_score'] ?? 0);
                ?>
                <tr<?= $spam > 0 ? ' class="row--flagged"' : '' ?>>
                    <td data-label="Patient">
                        <span class="cell-strong"><?= e((string) ($row['full_name'] ?? '')) ?></span>
                        <span class="cell-meta">
                            <code><?= e((string) ($row['reference_code'] ?? '')) ?></code>
                            · <?= $isFollow ? 'Follow-up' : 'Consultation' ?>
                            <?= $row['age'] !== null && $row['age'] !== '' ? ' · age ' . e((string) $row['age']) : '' ?>
                        </span>
                        <?php if ($spam > 0): ?>
                            <span class="badge badge--muted">spam score <?= e((string) $spam) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Contact">
                        <a class="cell-link" href="<?= e(tel_link((string) ($row['phone'] ?? ''))) ?>"><?= e(format_phone((string) ($row['phone'] ?? ''))) ?></a>
                        <?php if (($row['email'] ?? '') !== ''): ?>
                            <a class="cell-link" href="<?= e(mail_link('Re: your appointment request ' . (string) ($row['reference_code'] ?? ''))) ?>"><?= e((string) $row['email']) ?></a>
                        <?php endif; ?>
                    </td>
                    <td data-label="Reason">
                        <span class="cell-strong"><?= e((string) ($row['reason'] ?? '—')) ?></span>
                        <?php if (($row['message'] ?? '') !== ''): ?>
                            <span class="cell-meta"><?= e(truncate((string) $row['message'], 110)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Preferred slot">
                        <span class="cell-strong"><?= e(format_date((string) ($row['preferred_date'] ?? ''), 'D, d M Y')) ?></span>
                        <span class="cell-meta"><?= e(minutes_to_label((string) ($row['preferred_time'] ?? ''))) ?></span>
                    </td>
                    <td data-label="Received">
                        <span title="<?= e((string) ($row['created_at'] ?? '')) ?>"><?= e(time_ago((string) ($row['created_at'] ?? ''))) ?></span>
                    </td>
                    <td data-label="Status">
                        <span class="<?= e(status_badge($status)) ?>"><?= e(status_label($status)) ?></span>
                    </td>
                    <td class="table__actions" data-label="Actions">
                        <?php if ($id < 1): ?>
                            <span class="cell-meta">
                                <?= ($row['storage_source'] ?? 'file') === 'mongo' ? 'MongoDB backup' : 'Saved locally' ?>
                                · status changes need MongoDB.
                            </span>
                        <?php else: ?>
                        <details class="row-menu">
                            <summary class="btn btn--ghost btn--xs" aria-label="Actions for <?= e((string) ($row['full_name'] ?? '')) ?>">
                                <?= icon('chevron', 'icon icon--xs', 14) ?> Actions
                            </summary>
                            <div class="row-menu__panel">
                                <form class="row-menu__form" method="post" action="<?= e(url('/admin/appointments/' . $id . '/status')) ?>">
                                    <?= csrf_field() ?>
                                    <label class="field__label" for="status-<?= e((string) $id) ?>">Set status</label>
                                    <select class="field__input" id="status-<?= e((string) $id) ?>" name="status">
                                        <?php foreach ($statuses as $option): ?>
                                            <option value="<?= e($option) ?>"<?= $option === $status ? ' selected' : '' ?>>
                                                <?= e($statusLabels[$option] ?? $option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="field__label" for="note-<?= e((string) $id) ?>">Internal note</label>
                                    <textarea class="field__input" id="note-<?= e((string) $id) ?>" name="admin_note" rows="2"
                                              maxlength="1000" placeholder="Optional note for your team"><?= e((string) ($row['admin_note'] ?? '')) ?></textarea>
                                    <label class="check">
                                        <input type="checkbox" name="notify" value="1">
                                        <span>Email the patient when confirming</span>
                                    </label>
                                    <button class="btn btn--primary btn--xs" type="submit">Save status</button>
                                </form>

                                <form class="row-menu__form" method="post" action="<?= e(url('/admin/appointments/' . $id . '/delete')) ?>"
                                      data-confirm="Delete the request from <?= e((string) ($row['full_name'] ?? 'this patient')) ?> permanently?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="confirm" value="<?= e((string) $id) ?>">
                                    <button class="btn btn--danger btn--xs" type="submit">
                                        <?= icon('trash', 'icon icon--xs', 14) ?> Delete permanently
                                    </button>
                                </form>
                            </div>
                        </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($lastPage > 1): ?>
    <?php
    $qs = static function (int $page) use ($filters): string {
        $params = $filters;
        $params['page'] = $page > 1 ? $page : null;
        $query = http_build_query(array_filter($params, static fn ($v): bool => (string) $v !== ''));

        return '/admin/appointments' . ($query === '' ? '' : '?' . $query);
    };
    $windowStart = max(1, $current - 2);
    $windowEnd   = min($lastPage, $windowStart + 4);
    $windowStart = max(1, $windowEnd - 4);
    ?>
    <nav class="pagination" aria-label="Appointment pages">
        <a class="pagination__link<?= $current <= 1 ? ' is-disabled' : '' ?>" href="<?= e(url($qs(max(1, $current - 1)))) ?>" rel="prev"<?= $current <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>
            <?= icon('arrow-left', 'icon icon--xs', 14) ?> Previous
        </a>
        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
            <a class="pagination__link<?= $i === $current ? ' is-active' : '' ?>" href="<?= e(url($qs($i))) ?>"
               <?= $i === $current ? 'aria-current="page"' : '' ?>><?= e((string) $i) ?></a>
        <?php endfor; ?>
        <a class="pagination__link<?= $current >= $lastPage ? ' is-disabled' : '' ?>" href="<?= e(url($qs(min($lastPage, $current + 1)))) ?>" rel="next"<?= $current >= $lastPage ? ' aria-disabled="true" tabindex="-1"' : '' ?>>
            Next <?= icon('arrow-right', 'icon icon--xs', 14) ?>
        </a>
    </nav>
    <p class="muted">Page <?= e((string) $current) ?> of <?= e((string) $lastPage) ?> · <?= e((string) $perPage) ?> per page</p>
<?php endif; ?>

<?php endif; ?>
