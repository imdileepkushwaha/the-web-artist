<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$tab = $_GET['tab'] ?? 'activity';
$pageNum = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($pageNum - 1) * $perPage;

$activityLog = getActivityLog($conn, $perPage, $offset);
$loginHistory = getLoginHistory($conn, $perPage, $offset);
$totalRows = $tab === 'logins' ? countLoginHistory($conn) : countActivityLog($conn);
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$pageTitle = 'Activity Log';
$activePage = 'activity';
require __DIR__ . '/includes/header.php';
?>

<div class="enquiry-stats-row">
    <a href="?tab=activity" class="enquiry-stat-pill <?= $tab === 'activity' ? 'active' : '' ?>">
        <span class="pill-label">Activity Log</span>
    </a>
    <a href="?tab=logins" class="enquiry-stat-pill <?= $tab === 'logins' ? 'active' : '' ?>">
        <span class="pill-label">Login History</span>
    </a>
</div>

<div class="panel">
    <?php
    $panelTitle = $tab === 'logins' ? 'Login History' : 'Activity Log';
    $panelMeta = $tab === 'logins' ? 'Recent admin sign-in attempts' : 'System actions and changes';
    $panelIconSvg = panelIconSvg($tab === 'logins' ? 'login' : 'activity');
    $panelIconColor = $tab === 'logins' ? 'blue' : 'purple';
    require __DIR__ . '/includes/panel-header.php';
    ?>
    <div class="panel-body">
        <div class="table-responsive">
            <?php if ($tab === 'logins'): ?>
                <table class="data-table">
                    <thead><tr><th>User</th><th>IP</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        <?php if (empty($loginHistory)): ?>
                            <tr><td colspan="4" class="empty-state">No login history yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($loginHistory as $row): ?>
                                <tr>
                                    <td><?= sanitize($row['username']) ?></td>
                                    <td><?= sanitize($row['ip_address'] ?? '—') ?></td>
                                    <td><?= $row['success'] ? '<span class="badge badge-contacted">Success</span>' : '<span class="badge badge-closed">Failed</span>' ?></td>
                                    <td><?= sanitize(formatEnquiryDate($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>User</th><th>Action</th><th>Details</th><th>When</th></tr></thead>
                    <tbody>
                        <?php if (empty($activityLog)): ?>
                            <tr><td colspan="4" class="empty-state">No activity yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activityLog as $row): ?>
                                <tr>
                                    <td><?= sanitize($row['username']) ?></td>
                                    <td><code><?= sanitize($row['action']) ?></code></td>
                                    <td><?= sanitize($row['details'] ?? ($row['entity_type'] ? $row['entity_type'] . ' #' . $row['entity_id'] : '—')) ?></td>
                                    <td><?= sanitize(formatEnquiryDate($row['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top:16px;">
                <?php
                $baseQuery = ['tab' => $tab];
                $prevQuery = http_build_query(array_merge($baseQuery, ['page' => max(1, $pageNum - 1)]));
                $nextQuery = http_build_query(array_merge($baseQuery, ['page' => min($totalPages, $pageNum + 1)]));
                ?>
                <a href="?<?= $prevQuery ?>" class="page-link <?= $pageNum <= 1 ? 'disabled' : '' ?>">← Prev</a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $i])) ?>" class="page-link <?= $i === $pageNum ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?<?= $nextQuery ?>" class="page-link <?= $pageNum >= $totalPages ? 'disabled' : '' ?>">Next →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
