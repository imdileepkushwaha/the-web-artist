<?php
$emailLogPage = max(1, (int) ($_GET['page'] ?? 1));
$emailLogPerPage = 25;
$emailLogOffset = ($emailLogPage - 1) * $emailLogPerPage;
$emailLogRows = getEmailLog($conn, $emailLogPerPage, $emailLogOffset);
$emailLogTotal = countEmailLog($conn);
$emailLogPages = max(1, (int) ceil($emailLogTotal / $emailLogPerPage));
?>
<div class="panel settings-card">
    <?php
    $panelTitle = 'Email Delivery Log';
    $panelMeta = 'Recent sent and failed emails from the admin panel';
    $panelIconSvg = panelIconSvg('email');
    $panelIconColor = 'orange';
    $panelAccent = true;
    require __DIR__ . '/panel-header.php';
    ?>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>To</th><th>Subject</th><th>Context</th><th>Status</th><th>Details</th><th>When</th></tr></thead>
                <tbody>
                    <?php if (empty($emailLogRows)): ?>
                        <tr><td colspan="6" class="empty-state">No emails logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($emailLogRows as $row): ?>
                            <tr>
                                <td><?= sanitize($row['recipient']) ?></td>
                                <td><?= sanitize($row['subject']) ?></td>
                                <td><code><?= sanitize($row['context']) ?></code></td>
                                <td><?= $row['status'] === 'sent' ? '<span class="badge badge-contacted">Sent</span>' : '<span class="badge badge-closed">Failed</span>' ?></td>
                                <td class="email-log-message"><?= sanitize($row['message'] ?? '') ?></td>
                                <td><?= sanitize(formatEnquiryDate($row['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($emailLogPages > 1): ?>
            <div class="pagination" style="margin-top:16px;">
                <?php for ($i = 1; $i <= $emailLogPages; $i++): ?>
                    <a href="settings.php?tab=email-log&amp;page=<?= $i ?>" class="page-link <?= $i === $emailLogPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
