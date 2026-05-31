<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$conn = getAdminDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isViewer()) {
    $bulkAction = $_POST['bulk_action'] ?? '';
    $selectedIds = array_map('intval', $_POST['selected'] ?? []);

    if ($bulkAction && !empty($selectedIds)) {
        if ($bulkAction === 'delete') {
            $count = bulkDeleteEnquiries($conn, $selectedIds);
            logActivity($conn, 'bulk_delete', 'enquiries', null, "Deleted {$count} enquiries");
            flashMessage('success', "{$count} enquiry/enquiries deleted.");
        } elseif (array_key_exists($bulkAction, enquiryStatuses())) {
            $count = bulkUpdateEnquiryStatus($conn, $selectedIds, $bulkAction);
            logActivity($conn, 'bulk_status', 'enquiries', null, "Updated {$count} to {$bulkAction}");
            flashMessage('success', "{$count} enquiry/enquiries updated.");
        } else {
            flashMessage('error', 'Invalid bulk action selected.');
        }
    } else {
        flashMessage('error', 'Select at least one enquiry and choose a bulk action.');
    }

    header('Location: enquiries.php?' . http_build_query(array_filter([
        'status' => $_POST['redirect_status'] ?? '',
        'search' => $_POST['redirect_search'] ?? '',
        'page' => $_POST['redirect_page'] ?? '',
    ])));
    exit;
}

$stats = getDashboardStats($conn);

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
    'follow_up' => trim($_GET['follow_up'] ?? ''),
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = getEnquiries($conn, $filters, $page);
$enquiries = $result['items'];
$totalPages = $result['total_pages'];
$total = $result['total'];
$showingFrom = $total === 0 ? 0 : (($page - 1) * $result['per_page']) + 1;
$showingTo = min($total, $page * $result['per_page']);

$pageTitle = 'Enquiries';
$activePage = 'enquiries.php';
require __DIR__ . '/includes/header.php';

$queryBase = http_build_query(array_filter([
    'status' => $filters['status'],
    'search' => $filters['search'],
    'follow_up' => $filters['follow_up'],
]));

$followUpDueCount = getFollowUpDueCount($conn);

$statusTabs = [
    '' => ['label' => 'All', 'count' => $stats['total'], 'class' => ''],
    'new' => ['label' => 'New', 'count' => $stats['new'], 'class' => 'pill-new'],
    'read' => ['label' => 'Read', 'count' => $stats['read'], 'class' => 'pill-read'],
    'contacted' => ['label' => 'Contacted', 'count' => $stats['contacted'], 'class' => 'pill-contacted'],
    'closed' => ['label' => 'Closed', 'count' => $stats['closed'], 'class' => 'pill-closed'],
];
?>

<div class="enquiry-stats-row">
    <?php foreach ($statusTabs as $statusKey => $tab): ?>
        <?php
        $tabQuery = $statusKey !== '' ? '?status=' . urlencode($statusKey) : '';
        if ($filters['search'] !== '') {
            $tabQuery .= ($tabQuery ? '&' : '?') . 'search=' . urlencode($filters['search']);
        }
        $isActive = $filters['status'] === $statusKey;
        ?>
        <a href="enquiries.php<?= $tabQuery ?>" class="enquiry-stat-pill <?= sanitize($tab['class']) ?> <?= $isActive ? 'active' : '' ?>">
            <span class="pill-label"><?= sanitize($tab['label']) ?></span>
            <span class="pill-count"><?= (int) $tab['count'] ?></span>
        </a>
    <?php endforeach; ?>
    <?php if ($followUpDueCount > 0): ?>
        <a href="enquiries.php?follow_up=due" class="enquiry-stat-pill pill-follow-up <?= $filters['follow_up'] === 'due' ? 'active' : '' ?>">
            <span class="pill-label">Follow-ups Due</span>
            <span class="pill-count"><?= $followUpDueCount ?></span>
        </a>
    <?php endif; ?>
</div>

<div class="panel enquiries-panel">
    <div class="panel-header panel-header-with-search">
        <div class="panel-header-left">
            <div class="panel-header-icon">
                <div class="panel-icon blue"><?= panelIconSvg('enquiries') ?></div>
                <div>
                    <h2>Enquiry List</h2>
                    <?php if ($total > 0): ?>
                        <p class="panel-meta">Showing <?= $showingFrom ?>–<?= $showingTo ?> of <?= $total ?> results</p>
                    <?php else: ?>
                        <p class="panel-meta">No results to display</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="panel-header-actions">
            <form id="enquirySearchForm" class="panel-header-search" method="GET" action="enquiries.php">
                <?php if ($filters['status'] !== ''): ?>
                    <input type="hidden" name="status" value="<?= sanitize($filters['status']) ?>">
                <?php endif; ?>
                <?php if ($filters['follow_up'] !== ''): ?>
                    <input type="hidden" name="follow_up" value="<?= sanitize($filters['follow_up']) ?>">
                <?php endif; ?>
                <div class="search-input-wrap">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" id="search" name="search" value="<?= sanitize($filters['search']) ?>" placeholder="Search name, email, phone..." autocomplete="off">
                    <button type="button" class="search-reset-btn" id="searchResetBtn" aria-label="Clear search" <?= $filters['search'] === '' ? 'hidden' : '' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </form>
            <a href="export.php?<?= $queryBase ?>" class="panel-header-export-btn" title="Export CSV" aria-label="Export CSV">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>
    <div class="panel-body">
        <?php if (empty($enquiries)): ?>
            <div class="empty-state enquiries-empty">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                </div>
                <h3>No enquiries found</h3>
                <p>Try changing your search or status filter, or wait for new form submissions.</p>
                <a href="enquiries.php" class="btn btn-primary btn-sm" style="margin-top:16px;">Clear Filters</a>
            </div>
        <?php else: ?>
            <form method="POST" id="bulkForm">
                <input type="hidden" name="redirect_status" value="<?= sanitize($filters['status']) ?>">
                <input type="hidden" name="redirect_search" value="<?= sanitize($filters['search']) ?>">
                <input type="hidden" name="redirect_page" value="<?= $page ?>">
                <?php if (!isViewer()): ?>
                <div class="bulk-actions-bar" id="bulkActionsBar">
                    <div class="bulk-actions-left">
                        <span class="bulk-selected-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            <span><strong id="bulkSelectedCount">0</strong> selected</span>
                        </span>
                    </div>
                    <div class="bulk-actions-center">
                        <label for="bulkActionSelect" class="bulk-action-label">Bulk action</label>
                        <select name="bulk_action" id="bulkActionSelect" class="bulk-action-select">
                            <option value="">Choose action...</option>
                            <?php foreach (enquiryStatuses() as $value => $label): ?>
                                <option value="<?= sanitize($value) ?>">Mark as <?= sanitize($label) ?></option>
                            <?php endforeach; ?>
                            <option value="delete">Delete selected</option>
                        </select>
                    </div>
                    <div class="bulk-actions-right">
                        <button type="button" class="btn btn-secondary btn-sm" id="bulkClearBtn">Clear selection</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="bulkApplyBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Apply
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            <div class="table-responsive">
                <table class="data-table enquiries-table">
                    <thead>
                        <tr>
                            <?php if (!isViewer()): ?><th class="col-check"><input type="checkbox" id="selectAllEnquiries" aria-label="Select all"></th><?php endif; ?>
                            <th>Contact</th>
                            <th>Service</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enquiries as $enquiry): ?>
                            <tr class="enquiry-row <?= $enquiry['status'] === 'new' ? 'row-new' : '' ?>">
                                <?php if (!isViewer()): ?>
                                <td><input type="checkbox" name="selected[]" value="<?= (int) $enquiry['id'] ?>" class="enquiry-checkbox"></td>
                                <?php endif; ?>
                                <td>
                                    <div class="contact-cell">
                                        <div class="avatar"><?= sanitize(getInitials($enquiry['name'])) ?></div>
                                        <div>
                                            <div class="table-name">
                                                <?= sanitize($enquiry['name']) ?>
                                                <span class="enquiry-id">#<?= (int) $enquiry['id'] ?></span>
                                            </div>
                                            <div class="table-sub"><?= sanitize($enquiry['email']) ?></div>
                                            <div class="table-phone"><?= sanitize($enquiry['phone']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="service-tag"><?= sanitize($enquiry['service']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= sourceBadgeClass($enquiry['source'] ?? 'contact') ?>"><?= sanitize(enquirySources()[$enquiry['source'] ?? 'contact'] ?? 'Contact') ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-dot <?= statusBadgeClass($enquiry['status']) ?>">
                                        <?= sanitize(enquiryStatuses()[$enquiry['status']]) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <strong><?= sanitize(formatEnquiryDateShort($enquiry['created_at'])) ?></strong>
                                        <span><?= sanitize(formatEnquiryDate($enquiry['created_at'])) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="enquiry.php?id=<?= (int) $enquiry['id'] ?>" class="btn btn-primary btn-sm btn-icon">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            View
                                        </a>
                                        <a href="mailto:<?= sanitize($enquiry['email']) ?>" class="btn btn-secondary btn-sm btn-icon" title="Email">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </form>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <span class="pagination-info">Page <?= $page ?> of <?= $totalPages ?></span>
                    <div class="pagination-links">
                        <?php
                        $prevQuery = $queryBase ? $queryBase . '&page=' . ($page - 1) : 'page=' . ($page - 1);
                        $nextQuery = $queryBase ? $queryBase . '&page=' . ($page + 1) : 'page=' . ($page + 1);

                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>
                        <a href="enquiries.php?<?= $prevQuery ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">← Prev</a>
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php $pageQuery = $queryBase ? $queryBase . '&page=' . $i : 'page=' . $i; ?>
                            <a href="enquiries.php?<?= $pageQuery ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="enquiries.php?<?= $nextQuery ?>" class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>">Next →</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
