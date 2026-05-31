<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminRole();

$conn = getAdminDb();
$settings = getAllSettings($conn);

$allowedTabs = ['site', 'homepage', 'seo', 'password', 'email', 'templates'];
$activeTab = $_GET['tab'] ?? 'site';
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'site';
}

function settingsRedirect(string $tab, array $extra = []): void
{
    $params = array_merge(['tab' => $tab], $extra);
    header('Location: settings.php?' . http_build_query($params));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'site';

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $account = resolveCurrentAdminUser($conn);
        $newPassLength = mb_strlen($newPass, 'UTF-8');

        if ($current === '') {
            flashMessage('error', 'Please enter your current password.');
        } elseif ($newPass === '') {
            flashMessage('error', 'Please enter a new password.');
        } elseif ($newPassLength < 6) {
            flashMessage('error', 'New password must be at least 6 characters.');
        } elseif ($newPass !== $confirm) {
            flashMessage('error', 'Passwords do not match.');
        } elseif (!$account) {
            flashMessage('error', 'Unable to find your admin account.');
        } elseif (!verifyAdminCurrentPassword($account, $current)) {
            flashMessage('error', 'Current password is incorrect.');
        } elseif (updateAdminUserPassword($conn, (int) $account['id'], $newPass)) {
            $_SESSION[ADMIN_SESSION_USER_ID] = (int) $account['id'];
            logActivity($conn, 'password_change', 'user', (int) $account['id']);
            flashMessage('success', 'Password updated successfully.');
        } else {
            flashMessage('error', 'Unable to update password.');
        }

        settingsRedirect('password');
    }

    if ($action === 'homepage') {
        $homepageKeys = [
            'hero_badge', 'hero_title_line1', 'hero_title_accent', 'hero_title_line2', 'hero_subtitle', 'hero_tags',
            'hero_stat1_num', 'hero_stat1_label', 'hero_stat2_num', 'hero_stat2_label', 'hero_stat3_num', 'hero_stat3_label',
            'hero_form_badge', 'hero_form_title', 'hero_form_subtitle',
            'business_hours', 'site_address', 'site_address_line2',
            'about_badge', 'about_title_accent', 'about_title_sub', 'about_lead', 'about_desc',
            'whatsapp_default_message',
        ];

        foreach ($homepageKeys as $key) {
            setSetting($conn, $key, trim($_POST[$key] ?? ''));
        }

        setSetting($conn, 'site_location_enabled', isset($_POST['site_location_enabled']) ? '1' : '0');
        setSetting($conn, 'follow_up_email_reminder', isset($_POST['follow_up_email_reminder']) ? '1' : '0');

        logActivity($conn, 'settings_update', 'settings', null, 'Homepage content updated');
        flashMessage('success', 'Homepage content saved successfully.');
        settingsRedirect('homepage');
    }

    if ($action === 'seo') {
        setSetting($conn, 'seo_title', trim($_POST['seo_title'] ?? ''));
        setSetting($conn, 'seo_description', trim($_POST['seo_description'] ?? ''));
        setSetting($conn, 'seo_keywords', trim($_POST['seo_keywords'] ?? ''));
        setSetting($conn, 'google_analytics_id', trim($_POST['google_analytics_id'] ?? ''));
        setSetting($conn, 'og_image_url', trim($_POST['og_image_url'] ?? ''));

        logActivity($conn, 'settings_update', 'settings', null, 'SEO settings updated');
        flashMessage('success', 'SEO settings saved successfully.');
        settingsRedirect('seo');
    }

    if ($action === 'email') {
        setSetting($conn, 'smtp_enabled', isset($_POST['smtp_enabled']) ? '1' : '0');
        setSetting($conn, 'smtp_host', trim($_POST['smtp_host'] ?? ''));
        setSetting($conn, 'smtp_port', (string) max(1, (int) ($_POST['smtp_port'] ?? 587)));
        setSetting($conn, 'smtp_encryption', trim($_POST['smtp_encryption'] ?? 'tls'));
        setSetting($conn, 'smtp_username', trim($_POST['smtp_username'] ?? ''));

        $smtpPassword = trim($_POST['smtp_password'] ?? '');
        if ($smtpPassword !== '') {
            setSetting($conn, 'smtp_password', $smtpPassword);
        }

        logActivity($conn, 'settings_update', 'settings', null, 'SMTP settings updated');
        flashMessage('success', 'SMTP settings saved successfully.');
        settingsRedirect('email');
    }

    if ($action === 'template_save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');

        if ($name === '' || $subject === '' || $body === '') {
            flashMessage('error', 'All template fields are required.');
        } elseif ($id && updateEmailTemplate($conn, $id, $name, $subject, $body)) {
            flashMessage('success', 'Template updated.');
            settingsRedirect('templates');
        } elseif (!$id && createEmailTemplate($conn, $name, $subject, $body)) {
            flashMessage('success', 'Template created.');
            settingsRedirect('templates');
        } else {
            flashMessage('error', 'Unable to save template.');
            settingsRedirect('templates', $id ? ['edit_template' => $id] : []);
        }
    }

    if ($action === 'template_delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && deleteEmailTemplate($conn, $id)) {
            flashMessage('success', 'Template deleted.');
        } else {
            flashMessage('error', 'Unable to delete template.');
        }
        settingsRedirect('templates');
    }

    if ($action === 'site') {
    $notifyEmail = trim($_POST['notify_email'] ?? '');
    $siteEmail = trim($_POST['site_email'] ?? '');
    $sitePhone = trim($_POST['site_phone'] ?? '');
    $currentLogo = getSiteLogoPath($conn);
    $upload = handleSiteLogoUpload($currentLogo);

    if ($upload['message'] !== '' && !$upload['success']) {
        flashMessage('error', $upload['message']);
        settingsRedirect('site');
    }

    if ($siteEmail === '' && $notifyEmail !== '') {
        $siteEmail = $notifyEmail;
    }

    setSetting($conn, 'admin_email', trim($_POST['admin_email'] ?? ''));
    setSetting($conn, 'email_from_name', trim($_POST['email_from_name'] ?? ''));
    setSetting($conn, 'notify_email', $notifyEmail);
    setSetting($conn, 'notify_enquiries_enabled', isset($_POST['notify_enquiries_enabled']) ? '1' : '0');
    setSetting($conn, 'site_email', $siteEmail);
    setSetting($conn, 'site_phone', $sitePhone);
    setSetting($conn, 'session_timeout_minutes', (string) max(5, (int) ($_POST['session_timeout_minutes'] ?? 30)));

    if (!empty($_POST['reset_site_logo'])) {
        setSetting($conn, 'site_logo', 'images/twa-logo.png');
    } elseif ($upload['success'] && $upload['path']) {
        setSetting($conn, 'site_logo', $upload['path']);
    } else {
        $siteLogo = trim($_POST['site_logo'] ?? $currentLogo);
        if ($siteLogo !== '') {
            setSetting($conn, 'site_logo', $siteLogo);
        }
    }

    logActivity($conn, 'settings_update', 'settings', null, 'Site settings updated');
    flashMessage('success', 'Settings saved successfully.');
    settingsRedirect('site');
    }
}

$settings = getAllSettings($conn);
$emailTemplates = getEmailTemplates($conn);
$editTemplateId = (int) ($_GET['edit_template'] ?? 0);
$editTemplate = $editTemplateId ? getEmailTemplateById($conn, $editTemplateId) : null;

if ($editTemplateId) {
    $activeTab = 'templates';
}

$pageTitle = 'Settings';
$activePage = 'settings.php';
require __DIR__ . '/includes/header.php';

$settingsTabs = [
    'site' => [
        'label' => 'Site & Notifications',
        'desc' => 'Contact details & alerts',
        'icon' => panelIconSvg('site'),
    ],
    'homepage' => [
        'label' => 'Homepage',
        'desc' => 'Hero, about & contact',
        'icon' => panelIconSvg('pages'),
    ],
    'seo' => [
        'label' => 'SEO',
        'desc' => 'Meta tags & analytics',
        'icon' => panelIconSvg('traffic'),
    ],
    'password' => [
        'label' => 'Change Password',
        'desc' => 'Account security',
        'icon' => panelIconSvg('password'),
    ],
    'email' => [
        'label' => 'Email Setup',
        'desc' => 'SMTP server settings',
        'icon' => panelIconSvg('email'),
    ],
    'templates' => [
        'label' => 'Templates',
        'desc' => 'Quick reply emails',
        'icon' => panelIconSvg('templates'),
    ],
];
?>

<div class="settings-tabs-layout">
    <aside class="settings-tab-nav" aria-label="Settings sections">
        <?php foreach ($settingsTabs as $tabKey => $tab): ?>
            <a href="settings.php?tab=<?= urlencode($tabKey) ?>"
               class="settings-tab-link <?= $activeTab === $tabKey ? 'is-active' : '' ?>"
               data-settings-tab="<?= sanitize($tabKey) ?>">
                <span class="settings-tab-icon"><?= $tab['icon'] ?></span>
                <span class="settings-tab-text">
                    <strong><?= sanitize($tab['label']) ?></strong>
                    <span><?= sanitize($tab['desc']) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </aside>

    <div class="settings-tab-panels">
        <?php
        $activeTabFile = __DIR__ . '/includes/settings-tab-' . $activeTab . '.php';
        ?>
        <div class="settings-tab-panel is-active" data-settings-panel="<?= sanitize($activeTab) ?>">
            <?php if (is_file($activeTabFile)): ?>
                <?php require $activeTabFile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
