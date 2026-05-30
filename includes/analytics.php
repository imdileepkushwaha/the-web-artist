<?php

require_once __DIR__ . '/../config/database.php';

function ensureSiteVisitsTable(PDO $conn): void
{
    $conn->exec("CREATE TABLE IF NOT EXISTS site_visits (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(100) NOT NULL DEFAULT 'home',
        ip_hash VARCHAR(64) NOT NULL,
        visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visited_at (visited_at),
        INDEX idx_ip_hash (ip_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function recordWebsiteVisit(string $page = 'home'): void
{
    try {
        $conn = getDbConnection();
        ensureSiteVisitsTable($conn);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ipHash = hash('sha256', $ip);

        $stmt = $conn->prepare('INSERT INTO site_visits (page, ip_hash) VALUES (:page, :ip_hash)');
        $stmt->execute([
            ':page' => $page,
            ':ip_hash' => $ipHash,
        ]);
    } catch (Throwable $e) {
        // Do not break the public website if tracking fails.
    }
}

function getWebsiteVisitStats(PDO $conn): array
{
    ensureSiteVisitsTable($conn);

    $stats = [
        'total' => 0,
        'today' => 0,
        'week' => 0,
        'unique_today' => 0,
    ];

    $totalRow = $conn->query('SELECT COUNT(*) AS count FROM site_visits')->fetch();
    $stats['total'] = (int) ($totalRow['count'] ?? 0);

    $todayRow = $conn->query('SELECT COUNT(*) AS count FROM site_visits WHERE DATE(visited_at) = CURDATE()')->fetch();
    $stats['today'] = (int) ($todayRow['count'] ?? 0);

    $weekRow = $conn->query('SELECT COUNT(*) AS count FROM site_visits WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)')->fetch();
    $stats['week'] = (int) ($weekRow['count'] ?? 0);

    $uniqueRow = $conn->query('SELECT COUNT(DISTINCT ip_hash) AS count FROM site_visits WHERE DATE(visited_at) = CURDATE()')->fetch();
    $stats['unique_today'] = (int) ($uniqueRow['count'] ?? 0);

    return $stats;
}

function getRecentVisitDays(PDO $conn, int $days = 7): array
{
    ensureSiteVisitsTable($conn);

    $stmt = $conn->prepare("SELECT DATE(visited_at) AS visit_date, COUNT(*) AS hits
        FROM site_visits
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY DATE(visited_at)
        ORDER BY visit_date ASC");
    $stmt->bindValue(':days', $days - 1, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
