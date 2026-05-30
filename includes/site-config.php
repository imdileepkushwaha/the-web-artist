<?php

if (defined('SITE_PHONE')) {
    return;
}

define('SITE_NAME', 'The Web Artist');
define('SITE_TAGLINE', 'IT Solutions & Software Development');
define('SITE_DEFAULT_DESCRIPTION', 'The Web Artist delivers custom software solutions — Ecommerce, School & Hospital Management, MLM, and AI systems — for businesses across India.');
define('SITE_KEYWORDS', 'web development, software company, ecommerce software, school management software, hospital software, IT solutions India, The Web Artist');

function twaNormalizePhoneRaw(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    return $digits !== '' ? $digits : '919876543210';
}

function twaLoadPublicSiteSettings(): array
{
    $defaults = [
        'site_phone' => '+91 98765 43210',
        'site_email' => 'hello@thewebartist.com',
    ];
    $hasSiteEmail = false;
    $notifyEmail = null;

    try {
        require_once __DIR__ . '/../config/database.php';
        $conn = getDbConnection();
        $stmt = $conn->query("SELECT setting_key, setting_value FROM admin_settings
            WHERE setting_key IN ('site_phone', 'site_email', 'notify_email')");
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $key = $row['setting_key'];
            $value = trim((string) ($row['setting_value'] ?? ''));

            if ($value === '') {
                continue;
            }

            if ($key === 'site_phone') {
                $defaults['site_phone'] = $value;
            } elseif ($key === 'site_email') {
                $defaults['site_email'] = $value;
                $hasSiteEmail = true;
            } elseif ($key === 'notify_email') {
                $notifyEmail = $value;
            }
        }
    } catch (Throwable $e) {
        // Fall back to defaults when the database is unavailable.
    }

    if (!$hasSiteEmail && $notifyEmail) {
        $defaults['site_email'] = $notifyEmail;
    }

    return $defaults;
}

$siteSettings = twaLoadPublicSiteSettings();

define('SITE_PHONE', $siteSettings['site_phone']);
define('SITE_PHONE_RAW', twaNormalizePhoneRaw($siteSettings['site_phone']));
define('SITE_EMAIL', $siteSettings['site_email']);

function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = ($basePath === '/' || $basePath === '.') ? '' : rtrim($basePath, '/');

    return $protocol . '://' . $host . $basePath;
}

function renderSeoMeta(string $title, string $description, string $path = ''): void
{
    $baseUrl = getBaseUrl();
    $canonical = rtrim($baseUrl, '/') . ($path ? '/' . ltrim($path, '/') : '/');
    $ogImage = rtrim($baseUrl, '/') . '/images/twa-logo.png';

    echo '<title>' . htmlspecialchars($title) . '</title>' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta name="keywords" content="' . htmlspecialchars(SITE_KEYWORDS) . '">' . "\n";
    echo '<meta name="author" content="' . htmlspecialchars(SITE_NAME) . '">' . "\n";
    echo '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . htmlspecialchars($canonical) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . htmlspecialchars(SITE_NAME) . '">' . "\n";
    echo '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
}
