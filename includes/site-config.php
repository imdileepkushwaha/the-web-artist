<?php

if (defined('SITE_PHONE')) {
    return;
}

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

function twaLoadSectionsSettings(): array
{
    $keys = [
        'nav_show_portfolio', 'nav_show_faq',
        'services_badge', 'services_title', 'services_subtitle',
        'testimonials_badge', 'testimonials_title', 'testimonials_subtitle',
        'faq_badge', 'faq_title', 'faq_subtitle', 'faq_intro',
        'about_title_prefix', 'about_feature1_title', 'about_feature1_desc',
        'about_feature2_title', 'about_feature2_desc', 'about_feature3_title', 'about_feature3_desc',
        'about_card_title', 'about_card_text',
        'cta_badge', 'cta_title_line1', 'cta_title_accent', 'cta_subtitle',
        'cta_perk1', 'cta_perk2', 'cta_perk3', 'cta_btn_primary', 'cta_btn_secondary',
        'cta_trust1', 'cta_trust2', 'cta_trust3',
        'contact_section_badge', 'contact_section_title', 'contact_section_subtitle',
        'contact_form_badge', 'contact_form_title', 'contact_form_subtitle',
        'footer_text', 'social_linkedin', 'social_twitter', 'social_facebook', 'social_instagram',
    ];

    $defaults = [
        'nav_show_portfolio' => '1', 'nav_show_faq' => '1',
        'services_badge' => 'WHAT WE DO', 'services_title' => 'Our Premium Services', 'services_subtitle' => 'Comprehensive IT solutions tailored to your business needs.',
        'testimonials_badge' => 'TESTIMONIALS', 'testimonials_title' => 'What Our Clients Say', 'testimonials_subtitle' => 'Trusted by businesses across industries.',
        'faq_badge' => 'FAQ', 'faq_title' => 'Frequently Asked Questions', 'faq_subtitle' => 'Everything you need to know about our services.', 'faq_intro' => 'Got questions? We have answers. Browse our most common questions below.',
        'about_title_prefix' => 'Crafting Digital', 'about_feature1_title' => 'Custom Solutions', 'about_feature1_desc' => 'Tailored software built for your unique business workflows.',
        'about_feature2_title' => 'Scalable Architecture', 'about_feature2_desc' => 'Systems designed to grow with your business demands.',
        'about_feature3_title' => 'Dedicated Support', 'about_feature3_desc' => 'Responsive team ready to help whenever you need us.',
        'about_card_title' => 'Innovation First', 'about_card_text' => 'We combine cutting-edge technology with beautiful design.',
        'cta_badge' => 'Start Your Project Today', 'cta_title_line1' => 'Ready to Elevate Your', 'cta_title_accent' => 'Business?',
        'cta_subtitle' => 'Join hundreds of satisfied clients and transform your operations with modern, scalable IT solutions built for your industry.',
        'cta_perk1' => 'Free consultation', 'cta_perk2' => 'Secure & reliable', 'cta_perk3' => 'Quick turnaround',
        'cta_btn_primary' => 'Get Started Today', 'cta_btn_secondary' => 'Contact Sales',
        'cta_trust1' => '50+ Projects Delivered', 'cta_trust2' => '98% Client Satisfaction', 'cta_trust3' => '24/7 Dedicated Support',
        'contact_section_badge' => 'CONTACT US', 'contact_section_title' => 'Get In Touch With Us', 'contact_section_subtitle' => "Have questions or need a custom solution? We're here to help.",
        'contact_form_badge' => "We're Here to Help", 'contact_form_title' => 'Send a Message', 'contact_form_subtitle' => 'Have a question or need a custom solution? Write to us anytime.',
        'footer_text' => '© ' . date('Y') . ' The Web Artist. All rights reserved.',
        'social_linkedin' => '', 'social_twitter' => '', 'social_facebook' => '', 'social_instagram' => '',
    ];

    $settings = twaFetchAdminSettings($keys);

    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key]) || $settings[$key] === null || $settings[$key] === '') {
            if (!str_starts_with($key, 'social_') && !in_array($key, ['nav_show_portfolio', 'nav_show_faq'], true)) {
                $settings[$key] = $value;
            } else {
                $settings[$key] = $settings[$key] ?? $value;
            }
        }
    }

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
$__twaMeta = twaFetchAdminSettings(['site_name', 'site_tagline']);

define('SITE_NAME', ($__twaMeta['site_name'] ?? '') !== '' ? $__twaMeta['site_name'] : 'The Web Artist');
define('SITE_TAGLINE', ($__twaMeta['site_tagline'] ?? '') !== '' ? $__twaMeta['site_tagline'] : 'IT Solutions & Software Development');
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
    require_once __DIR__ . '/url.php';

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $protocol . '://' . $host . siteBasePath();
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
