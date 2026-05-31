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

function twaFetchAdminSettings(array $keys): array
{
    $defaults = [];
    $found = [];

    try {
        require_once __DIR__ . '/../config/database.php';
        $conn = getDbConnection();

        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ({$placeholders})");
        $stmt->execute(array_values($keys));

        foreach ($stmt->fetchAll() as $row) {
            $value = trim((string) ($row['setting_value'] ?? ''));
            if ($value !== '') {
                $found[$row['setting_key']] = $value;
            }
        }
    } catch (Throwable $e) {
        // Fall back to defaults when the database is unavailable.
    }

    foreach ($keys as $key) {
        $defaults[$key] = $found[$key] ?? null;
    }

    return $defaults;
}

function twaLoadPublicSiteSettings(): array
{
    $keys = ['site_phone', 'site_email', 'notify_email', 'whatsapp_default_message', 'site_logo'];
    $settings = twaFetchAdminSettings($keys);

    $defaults = [
        'site_phone' => '+91 98765 43210',
        'site_email' => 'hello@thewebartist.com',
        'site_logo' => 'images/twa-logo.png',
        'whatsapp_default_message' => 'Hi, I would like to know more about your services.',
    ];

    if (empty($settings['site_email']) && !empty($settings['notify_email'])) {
        $settings['site_email'] = $settings['notify_email'];
    }

    foreach ($defaults as $key => $value) {
        if (empty($settings[$key])) {
            $settings[$key] = $value;
        }
    }

    return $settings;
}

function twaLoadHomepageSettings(): array
{
    $keys = [
        'hero_badge', 'hero_title_line1', 'hero_title_accent', 'hero_title_line2', 'hero_subtitle', 'hero_tags',
        'hero_stat1_num', 'hero_stat1_label', 'hero_stat2_num', 'hero_stat2_label', 'hero_stat3_num', 'hero_stat3_label',
        'hero_form_badge', 'hero_form_title', 'hero_form_subtitle',
        'business_hours', 'site_address', 'site_address_line2', 'site_location_enabled',
        'about_badge', 'about_title_accent', 'about_title_sub', 'about_lead', 'about_desc',
    ];

    $defaults = [
        'hero_badge' => 'Trusted IT Solutions Partner',
        'hero_title_line1' => 'Transform Your',
        'hero_title_accent' => 'Business',
        'hero_title_line2' => 'With Smart Software',
        'hero_subtitle' => 'We deliver cutting-edge solutions — Ecommerce, School & Hospital Management, and AI Support Systems — built to scale with your growth.',
        'hero_tags' => 'Ecommerce,Healthcare,Education,AI Systems',
        'hero_stat1_num' => '50+', 'hero_stat1_label' => 'Projects',
        'hero_stat2_num' => '98%', 'hero_stat2_label' => 'Satisfaction',
        'hero_stat3_num' => '24/7', 'hero_stat3_label' => 'Support',
        'hero_form_badge' => 'Free Consultation',
        'hero_form_title' => 'Request a Demo',
        'hero_form_subtitle' => 'Share your requirements — our team will reach out within 24 hours.',
        'business_hours' => 'Mon – Sat, 9:00 AM – 6:00 PM IST',
        'site_address' => '', 'site_address_line2' => '', 'site_location_enabled' => '0',
        'about_badge' => 'About Us',
        'about_title_accent' => 'Excellence',
        'about_title_sub' => 'at The Web Artist',
        'about_lead' => 'We are a premier IT company dedicated to crafting high-quality, modern, and scalable software solutions for growing businesses.',
        'about_desc' => 'With years of experience, we empower organizations across healthcare, education, retail, and direct sales — turning ideas into reliable products through robust engineering and beautiful design.',
    ];

    $settings = twaFetchAdminSettings($keys);

    foreach ($defaults as $key => $value) {
        if (empty($settings[$key])) {
            $settings[$key] = $value;
        }
    }

    $settings['hero_tags_list'] = array_values(array_filter(array_map('trim', explode(',', $settings['hero_tags']))));

    return $settings;
}

function twaLoadSeoSettings(): array
{
    $keys = ['seo_title', 'seo_description', 'seo_keywords', 'google_analytics_id', 'og_image_url'];
    $defaults = [
        'seo_title' => 'The Web Artist - IT Solutions',
        'seo_description' => SITE_DEFAULT_DESCRIPTION,
        'seo_keywords' => SITE_KEYWORDS,
        'google_analytics_id' => '',
        'og_image_url' => '',
    ];

    $settings = twaFetchAdminSettings($keys);

    foreach ($defaults as $key => $value) {
        if (empty($settings[$key])) {
            $settings[$key] = $value;
        }
    }

    return $settings;
}

$siteSettings = twaLoadPublicSiteSettings();

define('SITE_PHONE', $siteSettings['site_phone']);
define('SITE_PHONE_RAW', twaNormalizePhoneRaw($siteSettings['site_phone']));
define('SITE_EMAIL', $siteSettings['site_email']);
define('SITE_WHATSAPP_MESSAGE', $siteSettings['whatsapp_default_message']);
define('SITE_LOGO', $siteSettings['site_logo'] ?? 'images/twa-logo.png');

function twaPublicAssetUrl(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return 'images/twa-logo.png';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return ltrim(str_replace('\\', '/', $path), '/');
}

function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = ($basePath === '/' || $basePath === '.') ? '' : rtrim($basePath, '/');

    return $protocol . '://' . $host . $basePath;
}

function renderSeoMeta(string $title = '', string $description = '', string $path = ''): void
{
    $seo = twaLoadSeoSettings();
    $baseUrl = getBaseUrl();
    $canonical = rtrim($baseUrl, '/') . ($path ? '/' . ltrim($path, '/') : '/');
    $pageTitle = $title !== '' ? $title : $seo['seo_title'];
    $pageDescription = $description !== '' ? $description : $seo['seo_description'];
    $keywords = $seo['seo_keywords'] ?: SITE_KEYWORDS;
    $ogImage = $seo['og_image_url'] !== '' ? $seo['og_image_url'] : rtrim($baseUrl, '/') . '/' . twaPublicAssetUrl(SITE_LOGO);

    echo '<title>' . htmlspecialchars($pageTitle) . '</title>' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($pageDescription) . '">' . "\n";
    echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    echo '<meta name="author" content="' . htmlspecialchars(SITE_NAME) . '">' . "\n";
    echo '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . htmlspecialchars($pageTitle) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($pageDescription) . '">' . "\n";
    echo '<meta property="og:url" content="' . htmlspecialchars($canonical) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . htmlspecialchars(SITE_NAME) . '">' . "\n";
    echo '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($pageTitle) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($pageDescription) . '">' . "\n";

    if (!empty($seo['google_analytics_id'])) {
        $gaId = preg_replace('/[^a-zA-Z0-9\-]/', '', $seo['google_analytics_id']);
        if ($gaId !== '') {
            echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaId}\"></script>\n";
            echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$gaId}');</script>\n";
        }
    }
}
