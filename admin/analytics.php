<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$conn = getAdminDb();
$view = $_GET['view'] ?? 'traffic';
if (!in_array($view, ['traffic', 'enquiries'], true)) {
    $view = 'traffic';
}

$days = max(7, min(90, (int) ($_GET['days'] ?? 30)));
$visitStats = getVisitStatsDetailed($conn, $days);
$enquiryStats = getDashboardStats($conn);
$sourceStats = getEnquirySourceStats($conn);
$serviceStats = getEnquiryServiceStats($conn, 8);
$monthlyTrend = getEnquiryMonthlyTrend($conn, 6);
$followUpDueCount = getFollowUpDueCount($conn);

$periodHits = 0;
foreach ($visitStats['daily'] as $day) {
    $periodHits += (int) ($day['hits'] ?? 0);
}

$closedRate = $enquiryStats['total'] > 0 ? round(($enquiryStats['closed'] / $enquiryStats['total']) * 100) : 0;
$heroCount = 0;
$contactCount = 0;
foreach ($sourceStats as $row) {
    if (($row['source'] ?? '') === 'hero') {
        $heroCount = (int) $row['count'];
    } elseif (($row['source'] ?? '') === 'contact') {
        $contactCount = (int) $row['count'];
    }
}
$totalSourceCount = $heroCount + $contactCount;
$heroShare = $totalSourceCount > 0 ? round(($heroCount / $totalSourceCount) * 100) : 0;

$maxSourceCount = 1;
foreach ($sourceStats as $row) {
    $maxSourceCount = max($maxSourceCount, (int) $row['count']);
}

$maxServiceEnqCount = 1;
foreach ($serviceStats as $row) {
    $maxServiceEnqCount = max($maxServiceEnqCount, (int) $row['count']);
}

$maxMonthlyCount = 1;
foreach ($monthlyTrend as $row) {
    $maxMonthlyCount = max($maxMonthlyCount, (int) $row['count']);
}

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

<div class="analytics-page">
    <div class="analytics-hero">
        <div class="analytics-hero-text">
            <h1 class="analytics-page-title">Analytics</h1>
            <p class="analytics-page-desc">Pehle overview dekho, phir tab se <strong>Traffic</strong> ya <strong>Enquiries</strong> detail me jao.</p>
        </div>
        <div class="analytics-insight-grid">
            <div class="analytics-insight-card">
                <span class="analytics-insight-label">Aaj ke hits</span>
                <strong><?= number_format($visitStats['today']) ?></strong>
                <small><?= number_format($visitStats['unique_today']) ?> unique</small>
            </div>
            <div class="analytics-insight-card">
                <span class="analytics-insight-label">Aaj ki enquiries</span>
                <strong><?= number_format($enquiryStats['today']) ?></strong>
                <small><?= number_format($enquiryStats['new']) ?> new pending</small>
            </div>
            <div class="analytics-insight-card">
                <span class="analytics-insight-label">Total leads</span>
                <strong><?= number_format($enquiryStats['total']) ?></strong>
                <small><?= $closedRate ?>% closed</small>
            </div>
            <div class="analytics-insight-card analytics-insight-card-alert">
                <span class="analytics-insight-label">Follow-ups due</span>
                <strong><?= number_format($followUpDueCount) ?></strong>
                <small><?= $followUpDueCount > 0 ? 'Action needed' : 'All clear' ?></small>
            </div>
        </div>
    </div>

    <div class="analytics-tabs" role="tablist" aria-label="Analytics views">
        <a href="analytics.php?view=traffic&amp;days=<?= $days ?>"
           class="analytics-tab <?= $view === 'traffic' ? 'active' : '' ?>"
           role="tab"
           aria-selected="<?= $view === 'traffic' ? 'true' : 'false' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
            <span>
                <strong>Website Traffic</strong>
                <small>Visits, charts & top pages</small>
            </span>
        </a>
        <a href="analytics.php?view=enquiries"
           class="analytics-tab <?= $view === 'enquiries' ? 'active' : '' ?>"
           role="tab"
           aria-selected="<?= $view === 'enquiries' ? 'true' : 'false' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
            <span>
                <strong>Enquiries</strong>
                <small>Leads, sources & services</small>
            </span>
        </a>
    </div>

    <?php if ($view === 'traffic'): ?>
    <section class="analytics-view">
        <div class="analytics-view-head">
            <div class="analytics-step-label">
                <span class="analytics-step-num">1</span>
                <div>
                    <h2>Traffic Overview</h2>
                    <p>Kitne log website dekh rahe hain — time period choose karo</p>
                </div>
            </div>
            <form method="GET" class="period-pills">
                <input type="hidden" name="view" value="traffic">
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
                    <div class="stat-label">All Time Hits</div>
                    <div class="stat-value"><?= number_format($visitStats['total']) ?></div>
                    <div class="stat-meta">Total page views</div>
                </div>
            </div>
            <div class="stat-card dash-stat-card accent-purple">
                <div class="dash-stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">Today</div>
                    <div class="stat-value"><?= number_format($visitStats['today']) ?></div>
                    <div class="stat-meta"><?= number_format($visitStats['unique_today']) ?> unique</div>
                </div>
            </div>
            <div class="stat-card dash-stat-card accent-green">
                <div class="dash-stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">Unique (7 days)</div>
                    <div class="stat-value"><?= number_format($visitStats['unique_week']) ?></div>
                    <div class="stat-meta">Different visitors</div>
                </div>
            </div>
            <div class="stat-card dash-stat-card accent-orange">
                <div class="dash-stat-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">Last <?= $days ?> Days</div>
                    <div class="stat-value"><?= number_format($periodHits) ?></div>
                    <div class="stat-meta">Selected period hits</div>
                </div>
            </div>
        </div>

        <div class="analytics-view-head analytics-view-head-secondary">
            <div class="analytics-step-label">
                <span class="analytics-step-num">2</span>
                <div>
                    <h2>Traffic Details</h2>
                    <p>Daily trend aur sabse zyada visit hone wale pages</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid analytics-grid">
            <div class="panel dash-panel analytics-chart-panel">
                <?php
                $panelTitle = 'Daily Traffic Chart';
                $panelMeta = 'Last ' . $days . ' days — bar height = hits';
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
                            <p>Website kholo — tracking automatically start ho jayegi.</p>
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
                $panelMeta = 'Ranked by total hits';
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
                                            <span class="analytics-page-stats"><?= (int) $page['hits'] ?> hits</span>
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
    </section>

    <?php else: ?>
    <section class="analytics-view">
        <div class="analytics-view-head">
            <div class="analytics-step-label">
                <span class="analytics-step-num">1</span>
                <div>
                    <h2>Enquiry Overview</h2>
                    <p>Total leads aur unka status — card pe click karke enquiries page kholo</p>
                </div>
            </div>
        </div>

        <div class="stats-grid dash-stats analytics-stats">
            <a href="enquiries.php" class="stat-card dash-stat-card">
                <div class="dash-stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">Total Enquiries</div>
                    <div class="stat-value"><?= number_format($enquiryStats['total']) ?></div>
                    <div class="stat-meta"><?= number_format($enquiryStats['week']) ?> this week</div>
                </div>
            </a>
            <a href="enquiries.php?status=new" class="stat-card dash-stat-card accent-purple">
                <div class="dash-stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">New</div>
                    <div class="stat-value"><?= number_format($enquiryStats['new']) ?></div>
                    <div class="stat-meta">Review pending</div>
                </div>
            </a>
            <a href="enquiries.php?status=closed" class="stat-card dash-stat-card accent-green">
                <div class="dash-stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">Closed</div>
                    <div class="stat-value"><?= number_format($enquiryStats['closed']) ?></div>
                    <div class="stat-meta"><?= $closedRate ?>% win rate</div>
                </div>
            </a>
            <a href="enquiries.php?follow_up=due" class="stat-card dash-stat-card accent-orange">
                <div class="dash-stat-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-card-content">
                    <div class="stat-label">Follow-ups Due</div>
                    <div class="stat-value"><?= number_format($followUpDueCount) ?></div>
                    <div class="stat-meta">Needs action</div>
                </div>
            </a>
        </div>

        <div class="analytics-view-head analytics-view-head-secondary">
            <div class="analytics-step-label">
                <span class="analytics-step-num">2</span>
                <div>
                    <h2>Monthly Trend</h2>
                    <p>Pichle 6 mahine me kitni enquiries aayi</p>
                </div>
            </div>
        </div>

        <div class="panel dash-panel analytics-chart-panel analytics-trend-panel">
            <?php
            $panelTitle = 'Enquiries Per Month';
            $panelMeta = 'Higher bar = more enquiries that month';
            $panelIconSvg = panelIconSvg('traffic');
            $panelIconColor = 'green';
            require __DIR__ . '/includes/panel-header.php';
            ?>
            <div class="panel-body">
                <?php if (empty($monthlyTrend)): ?>
                    <div class="empty-state"><p>No enquiry data yet.</p></div>
                <?php else: ?>
                    <div class="analytics-chart-wrap">
                        <div class="dash-visit-chart analytics-chart">
                            <?php foreach ($monthlyTrend as $month): ?>
                                <?php $barHeight = max(8, round(((int) $month['count'] / $maxMonthlyCount) * 100)); ?>
                                <div class="dash-visit-bar-wrap" title="<?= (int) $month['count'] ?> enquiries">
                                    <div class="dash-visit-bar analytics-bar analytics-bar-green" style="height: <?= $barHeight ?>%;"></div>
                                    <span><?= sanitize($month['month_label']) ?></span>
                                    <small><?= (int) $month['count'] ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="analytics-view-head analytics-view-head-secondary">
            <div class="analytics-step-label">
                <span class="analytics-step-num">3</span>
                <div>
                    <h2>Breakdown</h2>
                    <p>Kahan se enquiry aayi aur kaunsi service sabse zyada demand me hai</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid analytics-grid">
            <div class="panel dash-panel analytics-sources-panel">
                <?php
                $panelTitle = 'Enquiry Sources';
                $panelMeta = $totalSourceCount > 0 ? "Hero {$heroShare}% · Contact " . (100 - $heroShare) . '%' : 'Which form converts better';
                $panelIconSvg = panelIconSvg('enquiries');
                $panelIconColor = 'blue';
                require __DIR__ . '/includes/panel-header.php';
                ?>
                <div class="panel-body">
                    <?php if (empty($sourceStats)): ?>
                        <div class="empty-state"><p>No source data yet.</p></div>
                    <?php else: ?>
                        <div class="analytics-page-list">
                            <?php foreach ($sourceStats as $row): ?>
                                <?php
                                $sourceKey = $row['source'] ?? 'contact';
                                $sourceLabel = enquirySources()[$sourceKey] ?? ucfirst($sourceKey);
                                $width = round(((int) $row['count'] / $maxSourceCount) * 100);
                                $share = $enquiryStats['total'] > 0 ? round(((int) $row['count'] / $enquiryStats['total']) * 100) : 0;
                                ?>
                                <div class="analytics-page-item">
                                    <div class="analytics-page-content">
                                        <div class="analytics-page-top">
                                            <div class="analytics-source-label">
                                                <span class="badge <?= sourceBadgeClass($sourceKey) ?>"><?= sanitize($sourceLabel) ?></span>
                                                <strong><?= (int) $row['count'] ?></strong>
                                            </div>
                                            <span class="analytics-page-stats"><?= $share ?>%</span>
                                        </div>
                                        <div class="analytics-page-track">
                                            <div class="analytics-page-fill analytics-page-fill-blue" style="width: <?= $width ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel dash-panel analytics-pages-panel">
                <?php
                $panelTitle = 'Top Services';
                $panelMeta = 'Most enquired services';
                $panelIconSvg = panelIconSvg('top-services');
                $panelIconColor = 'purple';
                require __DIR__ . '/includes/panel-header.php';
                ?>
                <div class="panel-body">
                    <?php if (empty($serviceStats)): ?>
                        <div class="empty-state"><p>No service data yet.</p></div>
                    <?php else: ?>
                        <div class="analytics-page-list">
                            <?php foreach ($serviceStats as $index => $row): ?>
                                <?php $width = round(((int) $row['count'] / $maxServiceEnqCount) * 100); ?>
                                <div class="analytics-page-item">
                                    <div class="analytics-page-rank"><?= $index + 1 ?></div>
                                    <div class="analytics-page-content">
                                        <div class="analytics-page-top">
                                            <strong><?= sanitize($row['service']) ?></strong>
                                            <span class="analytics-page-stats"><?= (int) $row['count'] ?></span>
                                        </div>
                                        <div class="analytics-page-track">
                                            <div class="analytics-page-fill analytics-page-fill-purple" style="width: <?= $width ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
