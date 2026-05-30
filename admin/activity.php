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

$pageTitle = 'Activity Log';
require __DIR__ . '/includes/header.php';
?>

<div class="enquiry-stats-row">
    <a href="activity.php?tab=activity" class="enquiry-stat-pill <?= $tab === 'activity' ? 'active' : '' ?>">
        <span class="pill-label">Activity Log</span>
    </a>
    <a href="activity.php?tab=logins" class="enquiry-stat-pill <?= $tab === 'logins' ? 'active' : '' ?>">
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
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
