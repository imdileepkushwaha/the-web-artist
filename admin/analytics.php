<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$conn = getAdminDb();
$days = max(7, min(90, (int) ($_GET['days'] ?? 30)));
$visitStats = getVisitStatsDetailed($conn, $days);

$maxDailyHits = 1;
foreach ($visitStats['daily'] as $day) {
    $maxDailyHits = max($maxDailyHits, (int) $day['hits']);
}

$maxPageHits = 1;
foreach ($visitStats['by_page'] as $page) {
    $maxPageHits = max($maxPageHits, (int) $page['hits']);
}

$pageTitle = 'Analytics';
$activePage = 'analytics.php';
require __DIR__ . '/includes/header.php';
?>

<div class="page-toolbar analytics-toolbar">
    <div class="page-toolbar-left">
        <p class="page-toolbar-desc">Website traffic overview and page performance</p>
    </div>
    <form method="GET" class="period-pills">
        <?php foreach ([7 => '7D', 14 => '14D', 30 => '30D', 60 => '60D', 90 => '90D'] as $val => $label): ?>
            <button type="submit" name="days" value="<?= $val ?>" class="period-pill <?= $days === $val ? 'active' : '' ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </form>
</div>

<div class="stats-grid dash-stats analytics-stats">
    <div class="stat-card dash-stat-card">
        <div class="dash-stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">Total Hits</div>
            <div class="stat-value"><?= number_format($visitStats['total']) ?></div>
            <div class="stat-meta">All time page views</div>
        </div>
    </div>
    <div class="stat-card dash-stat-card accent-purple">
        <div class="dash-stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">Today</div>
            <div class="stat-value"><?= number_format($visitStats['today']) ?></div>
            <div class="stat-meta"><?= number_format($visitStats['unique_today']) ?> unique visitors</div>
        </div>
    </div>
    <div class="stat-card dash-stat-card accent-green">
        <div class="dash-stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">Unique This Week</div>
            <div class="stat-value"><?= number_format($visitStats['unique_week']) ?></div>
            <div class="stat-meta">Last 7 days</div>
        </div>
    </div>
    <div class="stat-card dash-stat-card accent-orange">
        <div class="dash-stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-card-content">
            <div class="stat-label">Last 30 Days</div>
            <div class="stat-value"><?= number_format($visitStats['month']) ?></div>
            <div class="stat-meta">Rolling period hits</div>
        </div>
    </div>
</div>

<div class="dashboard-grid analytics-grid">
    <div class="panel dash-panel analytics-chart-panel">
        <?php
        $panelTitle = 'Daily Traffic';
        $panelMeta = 'Hits over the last ' . $days . ' days';
        $panelIconSvg = panelIconSvg('traffic');
        $panelIconColor = 'blue';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <?php if (empty($visitStats['daily'])): ?>
                <div class="empty-state analytics-empty">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    </div>
                    <h3>No visit data yet</h3>
                    <p>Open the website to start tracking traffic.</p>
                </div>
            <?php else: ?>
                <div class="analytics-chart-wrap">
                    <div class="dash-visit-chart analytics-chart">
                        <?php foreach ($visitStats['daily'] as $day): ?>
                            <?php $barHeight = max(8, round(((int) $day['hits'] / $maxDailyHits) * 100)); ?>
                            <div class="dash-visit-bar-wrap" title="<?= (int) $day['hits'] ?> hits · <?= (int) $day['unique_visitors'] ?> unique">
                                <div class="dash-visit-bar analytics-bar" style="height: <?= $barHeight ?>%;"></div>
                                <span><?= sanitize(date('d M', strtotime($day['visit_date']))) ?></span>
                                <small><?= (int) $day['hits'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel dash-panel analytics-pages-panel">
        <?php
        $panelTitle = 'Top Pages';
        $panelMeta = 'Most visited pages on your site';
        $panelIconSvg = panelIconSvg('pages');
        $panelIconColor = 'purple';
        require __DIR__ . '/includes/panel-header.php';
        ?>
        <div class="panel-body">
            <?php if (empty($visitStats['by_page'])): ?>
                <div class="empty-state"><p>No page data yet.</p></div>
            <?php else: ?>
                <div class="analytics-page-list">
                    <?php foreach ($visitStats['by_page'] as $index => $page): ?>
                        <?php $width = round(((int) $page['hits'] / $maxPageHits) * 100); ?>
                        <div class="analytics-page-item">
                            <div class="analytics-page-rank"><?= $index + 1 ?></div>
                            <div class="analytics-page-content">
                                <div class="analytics-page-top">
                                    <code class="analytics-page-name">/<?= sanitize(ltrim($page['page'], '/')) ?></code>
                                    <span class="analytics-page-stats"><?= (int) $page['hits'] ?> hits · <?= (int) $page['unique_visitors'] ?> unique</span>
                                </div>
                                <div class="analytics-page-track">
                                    <div class="analytics-page-fill" style="width: <?= $width ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
