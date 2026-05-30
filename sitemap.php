<?php

require_once __DIR__ . '/includes/site-config.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = rtrim(getBaseUrl(), '/');
$lastMod = date('Y-m-d');

$pages = [
    ['loc' => $baseUrl . '/', 'priority' => '1.0'],
    ['loc' => $baseUrl . '/thank-you.php', 'priority' => '0.3'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $page) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($page['loc']) . "</loc>\n";
    echo '    <lastmod>' . $lastMod . "</lastmod>\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>' . $page['priority'] . "</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
