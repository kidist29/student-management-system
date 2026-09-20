<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Contact Messages';
$activeNav = 'messages';

// Mark-as-read (simple POST action from the list, no separate page needed).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    verifyCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $db->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
    redirect('admin/messages/index.php');
}

$sortColumns = ['name' => 'name', 'date' => 'created_at', 'read' => 'is_read'];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$orderBy = $sortKey ? safeOrderBy($sortColumns, $sortKey, 'date', $sortDir) : 'created_at DESC';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) $db->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT * FROM contact_messages ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute();
$messages = $stmt->fetchAll();

$unreadCount = (int) $db->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Messages</div>
                    <h1>Contact Messages <?php if ($unreadCount): ?><span class="badge badge-active"><?= $unreadCount ?> new</span><?php endif; ?></h1>
                </div>
            </div>

            <div class="card">
                <div class="card-body" style="padding-bottom:0;">
                    <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'messages') ?></div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th></th><th><?= sortableHeader('From', 'name', $sortKey, $sortDir) ?></th><th>Subject</th><th>Message</th><th><?= sortableHeader('Received', 'date', $sortKey, $sortDir) ?></th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$messages): ?>
                            <?= renderEmptyState(6, 'fa-envelope-open-text', 'No messages yet', 'Submissions from the public Contact page will show up here.') ?>
                        <?php endif; ?>
                        <?php foreach ($messages as $m): ?>
                            <tr style="<?= $m['is_read'] ? '' : 'background:var(--paper-100);' ?>">
                                <td><?php if (!$m['is_read']): ?><span title="Unread" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--gold-500);"></span><?php endif; ?></td>
                                <td>
                                    <div style="font-weight:600;"><?= h($m['name']) ?></div>
                                    <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($m['email']) ?></div>
                                </td>
                                <td><?= h($m['subject']) ?></td>
                                <td style="max-width:280px;white-space:normal;"><?= h(mb_strimwidth($m['message'], 0, 140, '…')) ?></td>
                                <td style="white-space:nowrap;"><?= h(date('M j, Y g:i A', strtotime($m['created_at']))) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <?php if (!$m['is_read']): ?>
                                        <form method="post" action="">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm" title="Mark as read"><i class="fa-solid fa-envelope-open"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                                data-delete-url="<?= BASE_URL ?>admin/messages/delete.php?id=<?= (int) $m['id'] ?>"
                                                data-delete-name="this message from <?= h($m['name']) ?>">
                                            <i class="fa-solid fa-trash" style="color:var(--danger);"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-body"><?= renderPagination($page, $totalPages) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="deleteConfirmModal">
    <div class="modal">
        <div class="modal-header"><h3>Delete message</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Delete <strong id="deleteConfirmName"></strong>? This cannot be undone.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
