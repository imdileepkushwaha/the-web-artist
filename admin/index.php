<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$conn = getAdminDb();
$stats = getDashboardStats($conn);

$dateFrom = trim($_GET['from'] ?? '');
$dateTo = trim($_GET['to'] ?? '');
if ($dateFrom !== '' || $dateTo !== '') {
    $stats = getDashboardStatsWithDateFilter($conn, $dateFrom ?: null, $dateTo ?: null);
}

$visitStats = getWebsiteVisitStats($conn);
$visitChart = getRecentVisitDays($conn, 7);
$recentEnquiries = getRecentEnquiries($conn, 6);
$serviceBreakdown = getServiceBreakdown($conn, 5);
$followUps = getFollowUpDueEnquiries($conn, 5);
$adminUsers = getAdminUsers($conn, true);

$maxServiceCount = 1;
foreach ($serviceBreakdown as $service) {
    $maxServiceCount = max($maxServiceCount, (int) $service['count']);
}

$maxVisitDay = 1;
foreach ($visitChart as $day) {
    $maxVisitDay = max($maxVisitDay, (int) $day['hits']);
}

$closedRate = $stats['total'] > 0 ? round(($stats['closed'] / $stats['total']) * 100) : 0;
$pendingCount = $stats['new'] + $stats['read'] + $stats['contacted'];

$pageTitle = 'Dashboard';
$activePage = 'index.php';
require __DIR__ . '/includes/header.php';
?>

<div class="panel-toolbar">
    <form method="GET" class="date-filter-form">
        <label>Date range:</label>
        <input type="date" name="from" value="<?= sanitize($dateFrom) ?>">
        <span>to</span>
        <input type="date" name="to" value="<?= sanitize($dateTo) ?>">
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if ($dateFrom || $dateTo): ?>
            <a href="index.php" class="btn btn-secondary btn-sm">Clear</a>
        <?php endif; ?>
    </form>
    <a href="export.php?<?= http_build_query(array_filter(['from' => $dateFrom, 'to' => $dateTo])) ?>" class="btn btn-secondary btn-sm">Export CSV</a>
</div>

<div class="dash-welcome">
    <div class="dash-welcome-content">
        <span class="dash-welcome-badge">Welcome back</span>
        <h2>Hello, <?= sanitize(adminDisplayName()) ?> 👋</h2>
        <p>Here's what's happening with your enquiries<?= ($dateFrom || $dateTo) ? ' in selected period' : ' today' ?>.</p>
        <div class="dash-quick-actions">
            <a href="enquiries.php?status=new" class="dash-action-btn primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                New Enquiries (<?= $stats['new'] ?>)
            </a>
            <a href="enquiries.php" class="dash-action-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                All Enquiries
            </a>
            <a href="analytics.php" class="dash-action-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                Analytics
            </a>
        </div>
    </div>
    <div class="dash-welcome-stats">
        <div class="dash-welcome-stat">
            <strong><?= number_format($visitStats['total']) ?></strong>
            <span>Website Hits</span>
        </div>
        <div class="dash-welcome-divider"></div>
        <div class="dash-welcome-stat">
            <strong><?= $visitStats['today'] ?></strong>
            <span>Hits Today</span>
        </div>
        <div class="dash-welcome-divider"></div>
        <div class="dash-welcome-stat">
            <strong><?= $visitStats['unique_today'] ?></strong>
            <span>Unique Today</span>
        </div>
    </div>
</div>

<?php if (!empty($followUps)): ?>
<div class="panel follow-up-panel">
    <?php
    $panelTitle = 'Follow-ups Due';
    $panelMeta = count($followUps) . ' enquir' . (count($followUps) === 1 ? 'y' : 'ies') . ' need attention';
    $panelIconSvg = panelIconSvg('follow-up');
    $panelIconColor = 'orange';
    require __DIR__ . '/includes/panel-header.php';
    ?>
    <div class="panel-body">
        <div class="dash-enquiry-list">
            <?php foreach ($followUps as $enquiry): ?>
                <a href="enquiry.php?id=<?= (int) $enquiry['id'] ?>" class="dash-enquiry-item is-new">
                    <div class="dash-enquiry-avatar"><?= sanitize(getInitials($enquiry['name'])) ?></div>
                    <div class="dash-enquiry-info">
                        <strong><?= sanitize($enquiry['name']) ?></strong>
                        <span class="dash-enquiry-service">Due: <?= sanitize($enquiry['follow_up_date']) ?><?= !empty($enquiry['assigned_name']) ? ' · ' . sanitize($enquiry['assigned_name']) : '' ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- rest of dashboard unchanged -->

<div class="stats-grid dash-stats">
    <a href="enquiries.php" class="stat-card dash-stat-card">
        <div class="dash-stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">Total Enquiries</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-meta">All time submissions</div>
        </div>
    </a>
    <a href="enquiries.php?status=new" class="stat-card dash-stat-card accent-purple">
        <div class="dash-stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">New Enquiries</div>
            <div class="stat-value"><?= $stats['new'] ?></div>
            <div class="stat-meta">Awaiting review</div>
        </div>
    </a>
    <div class="stat-card dash-stat-card accent-green">
        <div class="dash-stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">In Progress</div>
            <div class="stat-value"><?= $pendingCount ?></div>
            <div class="stat-meta"><?= $stats['contacted'] ?> contacted</div>
        </div>
    </div>
    <a href="enquiries.php?status=closed" class="stat-card dash-stat-card accent-orange">
        <div class="dash-stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">Closed</div>
            <div class="stat-value"><?= $stats['closed'] ?></div>
            <div class="stat-meta"><?= $closedRate ?>% completion</div>
        </div>
    </a>
</div>

<div class="dashboard-grid">
    <div class="panel dash-panel">
        <?php
        $panelTitle = 'Recent Enquiries';
        $panelMeta = 'Latest customer submissions';
        $panelIconSvg = panelIconSvg('enquiries');
        $panelIconColor = 'blue';
        $panelLinkHref = 'enquiries.php';
        $panelLinkLabel = 'View all →';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <?php if (empty($recentEnquiries)): ?>
                <div class="empty-state dash-empty">
                    <div class="dash-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                    </div>
                    <h3>No enquiries yet</h3>
                    <p>New form submissions will appear here.</p>
                </div>
            <?php else: ?>
                <div class="dash-enquiry-list">
                    <?php foreach ($recentEnquiries as $enquiry): ?>
                        <a href="enquiry.php?id=<?= (int) $enquiry['id'] ?>" class="dash-enquiry-item <?= $enquiry['status'] === 'new' ? 'is-new' : '' ?>">
                            <div class="dash-enquiry-avatar"><?= sanitize(getInitials($enquiry['name'])) ?></div>
                            <div class="dash-enquiry-info">
                                <div class="dash-enquiry-top">
                                    <strong><?= sanitize($enquiry['name']) ?></strong>
                                    <span class="badge <?= statusBadgeClass($enquiry['status']) ?>"><?= sanitize(enquiryStatuses()[$enquiry['status']]) ?></span>
                                </div>
                                <span class="dash-enquiry-service"><?= sanitize($enquiry['service']) ?></span>
                                <time><?= sanitize(formatEnquiryDateShort($enquiry['created_at'])) ?></time>
                            </div>
                            <svg class="dash-enquiry-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dash-sidebar-panels">
        <div class="panel dash-panel">
            <?php
            $panelTitle = 'Pipeline Status';
            $panelMeta = 'Enquiry workflow breakdown';
            $panelIconSvg = panelIconSvg('pipeline');
            $panelIconColor = 'purple';
            require __DIR__ . '/includes/panel-header.php';
            ?>
            <div class="dash-pipeline">
                <?php
                $pipelineItems = [
                    ['key' => 'new', 'label' => 'New', 'color' => '#2563eb', 'bg' => '#dbeafe'],
                    ['key' => 'read', 'label' => 'Read', 'color' => '#4338ca', 'bg' => '#e0e7ff'],
                    ['key' => 'contacted', 'label' => 'Contacted', 'color' => '#d97706', 'bg' => '#fef3c7'],
                    ['key' => 'closed', 'label' => 'Closed', 'color' => '#16a34a', 'bg' => '#dcfce7'],
                ];
                foreach ($pipelineItems as $item):
                    $count = $stats[$item['key']];
                    $percent = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                ?>
                    <a href="enquiries.php?status=<?= $item['key'] ?>" class="dash-pipeline-item">
                        <div class="dash-pipeline-top">
                            <span class="dash-pipeline-label">
                                <span class="dash-pipeline-dot" style="background:<?= $item['color'] ?>"></span>
                                <?= $item['label'] ?>
                            </span>
                            <span class="dash-pipeline-count"><?= $count ?> <small>(<?= $percent ?>%)</small></span>
                        </div>
                        <div class="dash-pipeline-track">
                            <div class="dash-pipeline-fill" style="width:<?= $percent ?>%; background:<?= $item['color'] ?>"></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel dash-panel">
            <?php
            $panelTitle = 'Website Traffic';
            $panelMeta = 'Last 7 days visit activity';
            $panelIconSvg = panelIconSvg('traffic');
            $panelIconColor = 'green';
            $panelLinkHref = 'analytics.php';
            $panelLinkLabel = 'Details →';
            require __DIR__ . '/includes/panel-header.php';
            ?>
            <div class="dash-traffic-summary">
                <div class="dash-traffic-stat">
                    <strong><?= number_format($visitStats['total']) ?></strong>
                    <span>Total Hits</span>
                </div>
                <div class="dash-traffic-stat">
                    <strong><?= $visitStats['today'] ?></strong>
                    <span>Today</span>
                </div>
                <div class="dash-traffic-stat">
                    <strong><?= $visitStats['unique_today'] ?></strong>
                    <span>Unique Today</span>
                </div>
            </div>
            <div class="dash-visit-chart">
                <?php if (empty($visitChart)): ?>
                    <div class="empty-state" style="padding:20px;">
                        <p>No visit data yet. Open the website to start tracking.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($visitChart as $day): ?>
                        <?php $barHeight = max(8, round(((int) $day['hits'] / $maxVisitDay) * 100)); ?>
                        <div class="dash-visit-bar-wrap" title="<?= (int) $day['hits'] ?> hits">
                            <div class="dash-visit-bar" style="height: <?= $barHeight ?>%;"></div>
                            <span><?= sanitize(date('D', strtotime($day['visit_date']))) ?></span>
                            <small><?= (int) $day['hits'] ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel dash-panel">
            <?php
            $panelTitle = 'Top Services';
            $panelMeta = 'Most requested solutions';
            $panelIconSvg = panelIconSvg('top-services');
            $panelIconColor = 'orange';
            require __DIR__ . '/includes/panel-header.php';
            ?>
            <div class="dash-services">
                <?php if (empty($serviceBreakdown)): ?>
                    <div class="empty-state" style="padding:24px;">
                        <p>No service data yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($serviceBreakdown as $index => $service): ?>
                        <?php $width = round(((int) $service['count'] / $maxServiceCount) * 100); ?>
                        <div class="dash-service-item">
                            <div class="dash-service-rank"><?= $index + 1 ?></div>
                            <div class="dash-service-content">
                                <div class="service-bar-top">
                                    <span><?= sanitize($service['service']) ?></span>
                                    <span><?= (int) $service['count'] ?></span>
                                </div>
                                <div class="service-bar-track">
                                    <div class="service-bar-fill" style="width: <?= $width ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>