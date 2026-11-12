<?php
/**
 * Contact-form messages.
 *
 * @var array<string,mixed> $clinic
 * @var array<int,array<string,mixed>> $rows
 * @var array<string,string> $filters
 * @var array{unread:int,total:int} $counts
 * @var int $current
 */

$filters = is_array($filters ?? null) ? $filters : [];
$counts  = is_array($counts ?? null) ? $counts : ['unread' => 0, 'total' => 0];
$current = max(1, (int) ($current ?? 1));
$perPage = 25;
$lastPage = max(1, (int) ceil(((int) ($counts['total'] ?? 0)) / $perPage));

$qs = static function (int $page) use ($filters): string {
    $params = $filters;
    $params['page'] = $page > 1 ? $page : null;
    $query = http_build_query(array_filter($params, static fn ($v): bool => (string) $v !== ''));

    return '/admin/messages' . ($query === '' ? '' : '?' . $query);
};
?>

<div class="admin-head">
    <div>
        <h1 class="admin-head__title">Messages</h1>
        <p class="admin-head__sub">
            <?= e((string) ($counts['unread'] ?? 0)) ?> unread of <?= e((string) ($counts['total'] ?? 0)) ?> message<?= (int) ($counts['total'] ?? 0) === 1 ? '' : 's' ?>.
            Opening a message marks it as read.
        </p>
    </div>
</div>

<nav class="filter-chips" aria-label="Filter by read status">
    <a class="filter-chip<?= ($filters['status'] ?? '') === '' ? ' is-active' : '' ?>" href="<?= e(url('/admin/messages')) ?>">
        All <span class="filter-chip__count"><?= e((string) ($counts['total'] ?? 0)) ?></span>
    </a>
    <a class="filter-chip<?= ($filters['status'] ?? '') === 'unread' ? ' is-active' : '' ?>" href="<?= e(url('/admin/messages?status=unread')) ?>">
        Unread <span class="filter-chip__count"><?= e((string) ($counts['unread'] ?? 0)) ?></span>
    </a>
    <a class="filter-chip<?= ($filters['status'] ?? '') === 'read' ? ' is-active' : '' ?>" href="<?= e(url('/admin/messages?status=read')) ?>">
        Read
    </a>
</nav>

<form class="filter-bar" method="get" action="<?= e(url('/admin/messages')) ?>">
    <div class="filter-bar__field">
        <label class="field__label" for="q">Search</label>
        <input class="field__input" type="search" id="q" name="q" maxlength="100"
               placeholder="Name, phone, email, subject or body" value="<?= e($filters['q'] ?? '') ?>">
    </div>
    <input type="hidden" name="status" value="<?= e($filters['status'] ?? '') ?>">
    <div class="filter-bar__actions">
        <button class="btn btn--primary btn--sm" type="submit"><?= icon('search', 'icon icon--xs', 15) ?> Search</button>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/messages')) ?>">Clear</a>
    </div>
</form>

<?php if ($rows === []): ?>
    <div class="admin-card">
        <div class="empty">
            <?= icon('inbox', 'icon', 28) ?>
            <p class="empty__text">No messages match this view.</p>
        </div>
    </div>
<?php else: ?>

<div class="admin-card">
    <ul class="msg-list">
        <?php foreach ($rows as $row): ?>
            <?php
            $id       = (int) ($row['id'] ?? 0);
            $isUnread = ($row['status'] ?? '') === 'unread';
            ?>
            <li class="msg<?= $isUnread ? ' msg--unread' : '' ?>">
                <?php if ($id > 0): ?><a class="msg__link" href="<?= e(url('/admin/messages/' . $id)) ?>"><?php else: ?><div class="msg__link"><?php endif; ?>
                    <span class="msg__avatar" aria-hidden="true"><?= e(initials((string) ($row['name'] ?? '?'))) ?></span>
                    <span class="msg__body">
                        <span class="msg__top">
                            <span class="msg__name"><?= e((string) ($row['name'] ?? '')) ?></span>
                            <span class="msg__time"><?= e(time_ago((string) ($row['created_at'] ?? ''))) ?></span>
                        </span>
                        <span class="msg__subject"><?= e((string) ($row['subject'] ?? 'General enquiry')) ?></span>
                        <span class="msg__preview"><?= e(truncate((string) ($row['message'] ?? ''), 150)) ?></span>
                    </span>
                    <?php if ($isUnread): ?>
                        <span class="msg__dot" aria-label="Unread"></span>
                    <?php endif; ?>
                <?php if ($id > 0): ?></a><?php else: ?><details class="msg__details"><summary>Read saved message</summary><p><?= nl2br(e((string) ($row['message'] ?? ''))) ?></p></details></div><?php endif; ?>
                <?php if ($id > 0): ?>
                <form class="msg__delete" method="post" action="<?= e(url('/admin/messages/' . $id . '/delete')) ?>"
                      data-confirm="Delete this message from <?= e((string) ($row['name'] ?? 'this sender')) ?> permanently?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="confirm" value="<?= e((string) $id) ?>">
                    <button class="btn btn--ghost btn--xs" type="submit" aria-label="Delete message">
                        <?= icon('trash', 'icon icon--xs', 14) ?>
                    </button>
                </form>
                <?php else: ?>
                    <span class="cell-meta">Saved locally · editing needs MongoDB.</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if ($lastPage > 1): ?>
    <nav class="pagination" aria-label="Message pages">
        <a class="pagination__link<?= $current <= 1 ? ' is-disabled' : '' ?>" href="<?= e(url($qs(max(1, $current - 1)))) ?>" rel="prev"<?= $current <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>
            <?= icon('arrow-left', 'icon icon--xs', 14) ?> Previous
        </a>
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
            <a class="pagination__link<?= $i === $current ? ' is-active' : '' ?>" href="<?= e(url($qs($i))) ?>"
               <?= $i === $current ? 'aria-current="page"' : '' ?>><?= e((string) $i) ?></a>
        <?php endfor; ?>
        <a class="pagination__link<?= $current >= $lastPage ? ' is-disabled' : '' ?>" href="<?= e(url($qs(min($lastPage, $current + 1)))) ?>" rel="next"<?= $current >= $lastPage ? ' aria-disabled="true" tabindex="-1"' : '' ?>>
            Next <?= icon('arrow-right', 'icon icon--xs', 14) ?>
        </a>
    </nav>
<?php endif; ?>

<?php endif; ?>
