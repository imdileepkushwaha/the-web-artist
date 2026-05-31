<?php

require_once __DIR__ . '/../../includes/analytics.php';
require_once __DIR__ . '/panel-icons.php';
require_once __DIR__ . '/mail-sender.php';

function getSetting(PDO $conn, string $key, ?string $default = null): ?string
{
    $stmt = $conn->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $row = $stmt->fetch();

    if (!$row) {
        return $default;
    }

    return $row['setting_value'] ?? $default;
}

function setSetting(PDO $conn, string $key, string $value): bool
{
    $stmt = $conn->prepare('INSERT INTO admin_settings (setting_key, setting_value) VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    return $stmt->execute([':key' => $key, ':value' => $value]);
}

function getSiteLogoPath(PDO $conn): string
{
    $logo = trim((string) getSetting($conn, 'site_logo', 'images/twa-logo.png'));

    return $logo !== '' ? $logo : 'images/twa-logo.png';
}

function handleSiteLogoUpload(?string $currentLogo = null): array
{
    if (empty($_FILES['site_logo_file']['name']) || (int) ($_FILES['site_logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => null, 'message' => ''];
    }

    $file = $_FILES['site_logo_file'];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'message' => 'Logo upload failed. Please try again.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'path' => null, 'message' => 'Logo must be 2 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    if (!isset($allowed[$mime])) {
        return ['success' => false, 'path' => null, 'message' => 'Logo must be PNG, JPG, WEBP, GIF, or SVG.'];
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/site';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'path' => null, 'message' => 'Unable to create upload folder.'];
    }

    $filename = 'logo-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'message' => 'Unable to save uploaded logo.'];
    }

    if ($currentLogo && strpos(str_replace('\\', '/', $currentLogo), 'uploads/site/') === 0) {
        $oldPath = dirname(__DIR__, 2) . '/' . ltrim(str_replace('\\', '/', $currentLogo), '/');
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return ['success' => true, 'path' => 'uploads/site/' . $filename, 'message' => 'Logo uploaded successfully.'];
}

function getAllSettings(PDO $conn): array
{
    $rows = $conn->query('SELECT setting_key, setting_value FROM admin_settings ORDER BY setting_key ASC')->fetchAll();
    $settings = [];

    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function logActivity(PDO $conn, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    $userId = $_SESSION[ADMIN_SESSION_USER_ID] ?? null;
    $username = $_SESSION[ADMIN_SESSION_USER] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare('INSERT INTO activity_log (user_id, username, action, entity_type, entity_id, details, ip_address)
        VALUES (:user_id, :username, :action, :entity_type, :entity_id, :details, :ip_address)');
    $stmt->execute([
        ':user_id' => $userId,
        ':username' => $username,
        ':action' => $action,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':details' => $details,
        ':ip_address' => $ip,
    ]);
}

function getAdminUsers(PDO $conn, bool $activeOnly = false): array
{
    $sql = 'SELECT id, username, name, email, role, is_active, last_login, created_at FROM admin_users';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY username ASC';

    return $conn->query($sql)->fetchAll();
}

function getAdminUserAccount(PDO $conn, string $username): ?array
{
    $stmt = $conn->prepare('SELECT id, username, password_hash, name, email, role, is_active
        FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function resolveCurrentAdminUser(PDO $conn): ?array
{
    $userId = adminUserId();

    if ($userId > 0) {
        $stmt = $conn->prepare('SELECT id, username, password_hash, name, email, role, is_active
            FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }
    }

    $username = adminUser();

    if ($username === '') {
        return null;
    }

    $account = getAdminUserAccount($conn, $username);

    return ($account && (int) $account['is_active'] === 1) ? $account : null;
}

function verifyAdminCurrentPassword(array $account, string $currentPassword): bool
{
    if ($currentPassword === '') {
        return false;
    }

    if (password_verify($currentPassword, $account['password_hash'])) {
        return true;
    }

    return defined('ADMIN_USERNAME')
        && defined('ADMIN_PASSWORD')
        && defined('TWA_ALLOW_CONFIG_LOGIN')
        && TWA_ALLOW_CONFIG_LOGIN
        && $account['username'] === ADMIN_USERNAME
        && $currentPassword === ADMIN_PASSWORD;
}

function createAdminUser(PDO $conn, string $username, string $password, string $name, ?string $email, string $role = 'admin'): bool
{
    if (!in_array($role, ['admin', 'viewer'], true)) {
        return false;
    }

    $stmt = $conn->prepare('INSERT INTO admin_users (username, password_hash, name, email, role, is_active)
        VALUES (:username, :password_hash, :name, :email, :role, 1)');

    return $stmt->execute([
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':name' => $name,
        ':email' => $email,
        ':role' => $role,
    ]);
}

function updateAdminUser(PDO $conn, int $id, string $name, ?string $email, string $role): bool
{
    if ($id <= 0 || !in_array($role, ['admin', 'viewer'], true)) {
        return false;
    }

    $stmt = $conn->prepare('UPDATE admin_users SET name = :name, email = :email, role = :role WHERE id = :id');

    return $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':role' => $role,
        ':id' => $id,
    ]);
}

function updateAdminUserPassword(PDO $conn, int $userId, string $password): bool
{
    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare('UPDATE admin_users SET password_hash = :password_hash, force_password_change = 0 WHERE id = :id');
    $stmt->execute([
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
}

function adminSessionRole(): string
{
    return $_SESSION[ADMIN_SESSION_ROLE] ?? 'admin';
}

function isAdminRole(): bool
{
    return adminSessionRole() === 'admin';
}

function isViewer(): bool
{
    return adminSessionRole() === 'viewer';
}

function requireAdminRole(): void
{
    requireAdminLogin();

    if (!isAdminRole()) {
        http_response_code(403);
        flashMessage('error', 'You do not have permission to perform this action.');
        header('Location: index.php');
        exit;
    }
}

function twaStrlen(string $value): int
{
    return function_exists('mb_strlen') ? (int) mb_strlen($value, 'UTF-8') : strlen($value);
}

function twaStrimwidth(string $value, int $start, int $width, string $trimMarker = ''): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, $start, $width, $trimMarker, 'UTF-8');
    }

    $chunk = substr($value, $start, $width);

    return strlen($value) > $width ? $chunk . $trimMarker : $chunk;
}

function sendEnquiryNotificationEmail(array $enquiry): array
{
    return sendEnquiryNotificationIfEnabled($enquiry);
}

function getEmailWorkflowStatus(PDO $conn): array
{
    $fromEmail = trim((string) getSetting($conn, 'admin_email', ''));
    $notifyEmail = trim((string) getSetting($conn, 'notify_email', ''));
    $smtpEnabled = getSetting($conn, 'smtp_enabled', '0') === '1';
    $smtpHost = trim((string) getSetting($conn, 'smtp_host', ''));
    $templateCount = (int) $conn->query('SELECT COUNT(*) FROM email_templates')->fetchColumn();

    return [
        'sender_ready' => $fromEmail !== '' && filter_var($fromEmail, FILTER_VALIDATE_EMAIL),
        'notify_ready' => $notifyEmail !== '' && filter_var($notifyEmail, FILTER_VALIDATE_EMAIL),
        'smtp_ready' => !$smtpEnabled || $smtpHost !== '',
        'templates_ready' => $templateCount > 0,
        'template_count' => $templateCount,
    ];
}

function getDashboardStatsWithDateFilter(PDO $conn, ?string $from, ?string $to): array
{
    $stats = [
        'total' => 0,
        'new' => 0,
        'read' => 0,
        'contacted' => 0,
        'closed' => 0,
        'today' => 0,
        'week' => 0,
    ];

    $where = [];
    $params = [];

    if ($from) {
        $where[] = 'DATE(created_at) >= :from_date';
        $params[':from_date'] = $from;
    }

    if ($to) {
        $where[] = 'DATE(created_at) <= :to_date';
        $params[':to_date'] = $to;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM enquiries {$whereSql}");
    $countStmt->execute($params);
    $stats['total'] = (int) ($countStmt->fetch()['total'] ?? 0);

    $statusStmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM enquiries {$whereSql} GROUP BY status");
    $statusStmt->execute($params);

    foreach ($statusStmt->fetchAll() as $row) {
        $stats[$row['status']] = (int) $row['count'];
    }

    $todayStmt = $conn->prepare("SELECT COUNT(*) AS count FROM enquiries WHERE DATE(created_at) = CURDATE()");
    $todayStmt->execute();
    $stats['today'] = (int) ($todayStmt->fetch()['count'] ?? 0);

    $weekStmt = $conn->prepare('SELECT COUNT(*) AS count FROM enquiries WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
    $weekStmt->execute();
    $stats['week'] = (int) ($weekStmt->fetch()['count'] ?? 0);

    return $stats;
}

function bulkUpdateEnquiryStatus(PDO $conn, array $ids, string $status): int
{
    if (empty($ids) || !array_key_exists($status, enquiryStatuses())) {
        return 0;
    }

    $ids = array_values(array_filter(array_map('intval', $ids)));

    if (empty($ids)) {
        return 0;
    }

    $currentStmt = $conn->prepare('SELECT id, status FROM enquiries WHERE id = ?');
    $updateStmt = $conn->prepare('UPDATE enquiries SET status = ? WHERE id = ?');
    $updated = 0;

    foreach ($ids as $id) {
        $currentStmt->execute([$id]);
        $row = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || enquiryStatusTransitionError($row['status'], $status) !== null) {
            continue;
        }

        if ($updateStmt->execute([$status, $id])) {
            $updated += $updateStmt->rowCount();
        }
    }

    return $updated;
}

function bulkDeleteEnquiries(PDO $conn, array $ids): int
{
    $ids = array_values(array_filter(array_map('intval', $ids)));

    if (empty($ids)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $notesStmt = $conn->prepare("DELETE FROM enquiry_notes WHERE enquiry_id IN ({$placeholders})");
    $notesStmt->execute($ids);

    $stmt = $conn->prepare("DELETE FROM enquiries WHERE id IN ({$placeholders})");
    $stmt->execute($ids);

    return $stmt->rowCount();
}

function updateEnquiryExtended(PDO $conn, int $id, ?string $status = null, ?int $assignedTo = null, ?string $followUpDate = null, bool $clearAssignedTo = false, bool $clearFollowUpDate = false): bool
{
    $fields = [];
    $params = [':id' => $id];

    if ($status !== null) {
        if (!array_key_exists($status, enquiryStatuses())) {
            return false;
        }

        $currentStmt = $conn->prepare('SELECT status FROM enquiries WHERE id = :id');
        $currentStmt->execute([':id' => $id]);
        $currentStatus = $currentStmt->fetchColumn();

        if ($currentStatus === false || enquiryStatusTransitionError($currentStatus, $status) !== null) {
            return false;
        }

        $fields[] = 'status = :status';
        $params[':status'] = $status;
    }

    if ($assignedTo !== null) {
        $fields[] = 'assigned_to = :assigned_to';
        $params[':assigned_to'] = $assignedTo;
    } elseif ($clearAssignedTo) {
        $fields[] = 'assigned_to = NULL';
    }

    if ($followUpDate !== null) {
        $fields[] = 'follow_up_date = :follow_up_date';
        $params[':follow_up_date'] = $followUpDate;
    } elseif ($clearFollowUpDate) {
        $fields[] = 'follow_up_date = NULL';
    }

    if (empty($fields)) {
        return false;
    }

    $sql = 'UPDATE enquiries SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $conn->prepare($sql);

    return $stmt->execute($params);
}

function getFollowUpDueEnquiries(PDO $conn, int $limit = 50): array
{
    $stmt = $conn->prepare("SELECT e.*, u.name AS assigned_name
        FROM enquiries e
        LEFT JOIN admin_users u ON u.id = e.assigned_to
        WHERE e.follow_up_date IS NOT NULL
          AND e.follow_up_date <= CURDATE()
          AND e.status NOT IN ('closed')
        ORDER BY e.follow_up_date ASC, e.created_at ASC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getFollowUpTodayEnquiries(PDO $conn, int $limit = 50): array
{
    $stmt = $conn->prepare("SELECT e.*, u.name AS assigned_name
        FROM enquiries e
        LEFT JOIN admin_users u ON u.id = e.assigned_to
        WHERE e.follow_up_date = CURDATE()
          AND e.status NOT IN ('closed')
        ORDER BY e.created_at ASC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getFollowUpOverdueEnquiries(PDO $conn, int $limit = 50): array
{
    $stmt = $conn->prepare("SELECT e.*, u.name AS assigned_name
        FROM enquiries e
        LEFT JOIN admin_users u ON u.id = e.assigned_to
        WHERE e.follow_up_date IS NOT NULL
          AND e.follow_up_date < CURDATE()
          AND e.status NOT IN ('closed')
        ORDER BY e.follow_up_date ASC, e.created_at ASC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getFollowUpDueCount(PDO $conn): int
{
    $row = $conn->query("SELECT COUNT(*) AS count FROM enquiries
        WHERE follow_up_date IS NOT NULL
          AND follow_up_date <= CURDATE()
          AND status NOT IN ('closed')")->fetch();

    return (int) ($row['count'] ?? 0);
}

function maybeSendFollowUpReminderEmail(PDO $conn): void
{
    if (getSetting($conn, 'follow_up_email_reminder', '1') !== '1') {
        return;
    }

    $today = date('Y-m-d');
    if (getSetting($conn, 'follow_up_reminder_last_sent', '') === $today) {
        return;
    }

    $dueCount = getFollowUpDueCount($conn);
    if ($dueCount === 0) {
        return;
    }

    $notifyEmail = trim((string) getSetting($conn, 'notify_email', ''));
    if ($notifyEmail === '') {
        return;
    }

    $overdue = getFollowUpOverdueEnquiries($conn, 5);
    $todayItems = getFollowUpTodayEnquiries($conn, 5);
    $body = "You have {$dueCount} enquiry/enquiries with follow-ups due.\n\n";

    if (!empty($todayItems)) {
        $body .= "Due today:\n";
        foreach ($todayItems as $item) {
            $body .= '- #' . $item['id'] . ' ' . ($item['name'] ?? '') . ' (' . ($item['service'] ?? '') . ")\n";
        }
        $body .= "\n";
    }

    if (!empty($overdue)) {
        $body .= "Overdue:\n";
        foreach ($overdue as $item) {
            $body .= '- #' . $item['id'] . ' ' . ($item['name'] ?? '') . ' — due ' . ($item['follow_up_date'] ?? '') . "\n";
        }
    }

    $body .= "\nLog in to the admin panel to review: enquiries.php?follow_up=due\n";

    $result = sendSystemEmail($notifyEmail, 'Follow-up Reminder: ' . $dueCount . ' due', $body);

    if ($result['success']) {
        setSetting($conn, 'follow_up_reminder_last_sent', $today);
    }
}

function getActivityLog(PDO $conn, int $limit = 50, int $offset = 0): array
{
    $stmt = $conn->prepare('SELECT * FROM activity_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getLoginHistory(PDO $conn, int $limit = 50, int $offset = 0): array
{
    $stmt = $conn->prepare('SELECT * FROM login_history ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getFaqItems(PDO $conn, bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM faq_items';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, id ASC';

    return $conn->query($sql)->fetchAll();
}

function getFaqItemById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM faq_items WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function createFaqItem(PDO $conn, string $question, string $answer, int $sortOrder = 0, bool $isActive = true): bool
{
    $stmt = $conn->prepare('INSERT INTO faq_items (question, answer, sort_order, is_active) VALUES (:question, :answer, :sort_order, :is_active)');

    return $stmt->execute([
        ':question' => $question,
        ':answer' => $answer,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
    ]);
}

function updateFaqItem(PDO $conn, int $id, string $question, string $answer, int $sortOrder, bool $isActive): bool
{
    $stmt = $conn->prepare('UPDATE faq_items SET question = :question, answer = :answer, sort_order = :sort_order, is_active = :is_active WHERE id = :id');

    return $stmt->execute([
        ':question' => $question,
        ':answer' => $answer,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
        ':id' => $id,
    ]);
}

function deleteFaqItem(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM faq_items WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function getTestimonials(PDO $conn, bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM testimonials';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, id ASC';

    return $conn->query($sql)->fetchAll();
}

function getTestimonialById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM testimonials WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function createTestimonial(PDO $conn, string $clientName, string $company, string $feedback, string $initials, int $sortOrder = 0, bool $isActive = true): bool
{
    $stmt = $conn->prepare('INSERT INTO testimonials (client_name, company, feedback, initials, sort_order, is_active)
        VALUES (:client_name, :company, :feedback, :initials, :sort_order, :is_active)');

    return $stmt->execute([
        ':client_name' => $clientName,
        ':company' => $company,
        ':feedback' => $feedback,
        ':initials' => $initials,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
    ]);
}

function updateTestimonial(PDO $conn, int $id, string $clientName, string $company, string $feedback, string $initials, int $sortOrder, bool $isActive): bool
{
    $stmt = $conn->prepare('UPDATE testimonials SET client_name = :client_name, company = :company, feedback = :feedback,
        initials = :initials, sort_order = :sort_order, is_active = :is_active WHERE id = :id');

    return $stmt->execute([
        ':client_name' => $clientName,
        ':company' => $company,
        ':feedback' => $feedback,
        ':initials' => $initials,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
        ':id' => $id,
    ]);
}

function deleteTestimonial(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM testimonials WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function getServices(PDO $conn, bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM services';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, id ASC';

    return $conn->query($sql)->fetchAll();
}

function getServiceById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function createService(PDO $conn, string $title, string $description, string $iconEmoji, int $sortOrder = 0, bool $isActive = true): bool
{
    $stmt = $conn->prepare('INSERT INTO services (title, description, icon_emoji, sort_order, is_active)
        VALUES (:title, :description, :icon_emoji, :sort_order, :is_active)');

    return $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':icon_emoji' => $iconEmoji,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
    ]);
}

function updateService(PDO $conn, int $id, string $title, string $description, string $iconEmoji, int $sortOrder, bool $isActive): bool
{
    $stmt = $conn->prepare('UPDATE services SET title = :title, description = :description, icon_emoji = :icon_emoji,
        sort_order = :sort_order, is_active = :is_active WHERE id = :id');

    return $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':icon_emoji' => $iconEmoji,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
        ':id' => $id,
    ]);
}

function deleteService(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM services WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function getEmailTemplates(PDO $conn): array
{
    return $conn->query('SELECT * FROM email_templates ORDER BY name ASC')->fetchAll();
}

function getEmailTemplateById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM email_templates WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function createEmailTemplate(PDO $conn, string $name, string $subject, string $body, bool $allowsAttachment = false): bool
{
    $stmt = $conn->prepare('INSERT INTO email_templates (name, subject, body, allows_attachment) VALUES (:name, :subject, :body, :allows_attachment)');

    return $stmt->execute([
        ':name' => $name,
        ':subject' => $subject,
        ':body' => $body,
        ':allows_attachment' => $allowsAttachment ? 1 : 0,
    ]);
}

function updateEmailTemplate(PDO $conn, int $id, string $name, string $subject, string $body, bool $allowsAttachment = false): bool
{
    $stmt = $conn->prepare('UPDATE email_templates SET name = :name, subject = :subject, body = :body, allows_attachment = :allows_attachment WHERE id = :id');

    return $stmt->execute([
        ':name' => $name,
        ':subject' => $subject,
        ':body' => $body,
        ':allows_attachment' => $allowsAttachment ? 1 : 0,
        ':id' => $id,
    ]);
}

function deleteEmailTemplate(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM email_templates WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function exportEnquiriesCsv(PDO $conn, array $filters = []): string
{
    $where = [];
    $params = [];

    if (!empty($filters['status']) && array_key_exists($filters['status'], enquiryStatuses())) {
        $where[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }

    if (!empty($filters['from'])) {
        $where[] = 'DATE(created_at) >= :from_date';
        $params[':from_date'] = $filters['from'];
    }

    if (!empty($filters['to'])) {
        $where[] = 'DATE(created_at) <= :to_date';
        $params[':to_date'] = $filters['to'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(name LIKE :search OR email LIKE :search OR phone LIKE :search OR service LIKE :search OR message LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $conn->prepare("SELECT * FROM enquiries {$whereSql} ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $output = fopen('php://temp', 'r+');
    fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Service', 'Source', 'Status', 'Message', 'Assigned To', 'Follow Up Date', 'Created At', 'Updated At']);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['service'],
            $row['source'] ?? 'contact',
            $row['status'],
            $row['message'],
            $row['assigned_to'] ?? '',
            $row['follow_up_date'] ?? '',
            $row['created_at'],
            $row['updated_at'] ?? '',
        ]);
    }

    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv ?: '';
}

function recordLoginAttempt(PDO $conn, ?int $userId, string $username, bool $success, ?string $ip, ?string $userAgent): void
{
    $stmt = $conn->prepare('INSERT INTO login_history (user_id, username, ip_address, user_agent, success)
        VALUES (:user_id, :username, :ip_address, :user_agent, :success)');
    $stmt->execute([
        ':user_id' => $userId,
        ':username' => $username,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
        ':success' => $success ? 1 : 0,
    ]);
}

function getAdminUserById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT id, username, name, email, role, is_active FROM admin_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function globalAdminSearch(PDO $conn, string $query, int $limit = 8): array
{
    $query = trim($query);

    if ($query === '') {
        return [];
    }

    $like = '%' . $query . '%';
    $results = [];

    $enqStmt = $conn->prepare('SELECT id, name, email, service, status FROM enquiries
        WHERE name LIKE :q OR email LIKE :q OR phone LIKE :q OR service LIKE :q
        ORDER BY created_at DESC LIMIT :limit');
    $enqStmt->bindValue(':q', $like);
    $enqStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $enqStmt->execute();

    foreach ($enqStmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'enquiry',
            'title' => $row['name'],
            'subtitle' => $row['service'] . ' · ' . $row['status'],
            'url' => 'enquiry.php?id=' . $row['id'],
        ];
    }

    $faqStmt = $conn->prepare('SELECT id, question FROM faq_items WHERE question LIKE :q OR answer LIKE :q ORDER BY sort_order ASC LIMIT :limit');
    $faqStmt->bindValue(':q', $like);
    $faqStmt->bindValue(':limit', max(1, $limit - count($results)), PDO::PARAM_INT);
    $faqStmt->execute();

    foreach ($faqStmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'faq',
            'title' => $row['question'],
            'subtitle' => 'FAQ item',
            'url' => 'faq.php?edit=' . $row['id'],
        ];
    }

    return array_slice($results, 0, $limit);
}

function generateDatabaseBackup(PDO $conn): string
{
    $tables = ['enquiries', 'enquiry_notes', 'admin_settings', 'admin_users', 'faq_items', 'testimonials', 'services', 'portfolio_projects', 'trusted_clients', 'email_templates', 'whatsapp_templates', 'email_log', 'activity_log', 'login_history', 'site_visits'];
    $sql = "-- The Web Artist Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        try {
            $create = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch();
            if (!$create) {
                continue;
            }

            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= ($create['Create Table'] ?? '') . ";\n\n";

            $rows = $conn->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $columns = array_map(static fn($col) => "`{$col}`", array_keys($row));
                $values = array_map(static function ($value) use ($conn) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return $conn->quote((string) $value);
                }, array_values($row));

                $sql .= "INSERT INTO `{$table}` (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
            }

            $sql .= "\n";
        } catch (Throwable $e) {
            continue;
        }
    }

    return $sql;
}

function applyEmailTemplate(string $body, array $enquiry): string
{
    $conn = getDbConnection();
    $sitePhone = getSetting($conn, 'site_phone', '+91 98765 43210');

    $replacements = [
        '{name}' => $enquiry['name'] ?? '',
        '{email}' => $enquiry['email'] ?? '',
        '{phone}' => $enquiry['phone'] ?? '',
        '{service}' => $enquiry['service'] ?? '',
        '{message}' => $enquiry['message'] ?? '',
        '{site_phone}' => $sitePhone ?? '',
        '{{name}}' => $enquiry['name'] ?? '',
        '{{email}}' => $enquiry['email'] ?? '',
        '{{phone}}' => $enquiry['phone'] ?? '',
        '{{service}}' => $enquiry['service'] ?? '',
        '{{message}}' => $enquiry['message'] ?? '',
        '{{site_phone}}' => $sitePhone ?? '',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $body);
}

function parseEmailAttachmentUpload(?array $file): array
{
    if ($file === null || empty($file['name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => null, 'name' => null, 'mime' => null, 'message' => ''];
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'name' => null, 'mime' => null, 'message' => 'Attachment upload failed. Please try again.'];
    }

    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        return ['success' => false, 'path' => null, 'name' => null, 'mime' => null, 'message' => 'Attachment must be 10 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
    ];

    if (!isset($allowed[$mime])) {
        return [
            'success' => false,
            'path' => null,
            'name' => null,
            'mime' => null,
            'message' => 'Attachment must be PDF, Word, Excel, PowerPoint, PNG, or JPG.',
        ];
    }

    $originalName = basename((string) ($file['name'] ?? 'attachment'));
    $originalName = preg_replace('/[^\w.\- ()]+/u', '_', $originalName) ?: 'attachment.' . $allowed[$mime];

    return [
        'success' => true,
        'path' => $file['tmp_name'],
        'name' => $originalName,
        'mime' => $mime,
        'message' => '',
    ];
}

function getVisitStatsDetailed(PDO $conn, int $days = 30): array
{
    ensureSiteVisitsTable($conn);

    $stats = getWebsiteVisitStats($conn);
    $stats['unique_week'] = 0;
    $stats['month'] = 0;
    $stats['daily'] = [];
    $stats['by_page'] = [];

    $uniqueWeekRow = $conn->query('SELECT COUNT(DISTINCT ip_hash) AS count FROM site_visits WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)')->fetch();
    $stats['unique_week'] = (int) ($uniqueWeekRow['count'] ?? 0);

    $monthRow = $conn->query('SELECT COUNT(*) AS count FROM site_visits WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')->fetch();
    $stats['month'] = (int) ($monthRow['count'] ?? 0);

    $dailyStmt = $conn->prepare("SELECT DATE(visited_at) AS visit_date,
            COUNT(*) AS hits,
            COUNT(DISTINCT ip_hash) AS unique_visitors
        FROM site_visits
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY DATE(visited_at)
        ORDER BY visit_date ASC");
    $dailyStmt->bindValue(':days', max(1, $days - 1), PDO::PARAM_INT);
    $dailyStmt->execute();
    $stats['daily'] = $dailyStmt->fetchAll();

    $pageStmt = $conn->query("SELECT page, COUNT(*) AS hits, COUNT(DISTINCT ip_hash) AS unique_visitors
        FROM site_visits
        GROUP BY page
        ORDER BY hits DESC
        LIMIT 10");
    $stats['by_page'] = $pageStmt ? $pageStmt->fetchAll() : [];

    return $stats;
}

function getEnquirySourceStats(PDO $conn, ?string $from = null, ?string $to = null): array
{
    $where = [];
    $params = [];

    if ($from) {
        $where[] = 'DATE(created_at) >= :from_date';
        $params[':from_date'] = $from;
    }

    if ($to) {
        $where[] = 'DATE(created_at) <= :to_date';
        $params[':to_date'] = $to;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $conn->prepare("SELECT source, COUNT(*) AS count FROM enquiries {$whereSql} GROUP BY source ORDER BY count DESC");
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getEnquiryServiceStats(PDO $conn, int $limit = 10, ?string $from = null, ?string $to = null): array
{
    $where = [];
    $params = [];

    if ($from) {
        $where[] = 'DATE(created_at) >= :from_date';
        $params[':from_date'] = $from;
    }

    if ($to) {
        $where[] = 'DATE(created_at) <= :to_date';
        $params[':to_date'] = $to;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $conn->prepare("SELECT service, COUNT(*) AS count FROM enquiries {$whereSql} GROUP BY service ORDER BY count DESC LIMIT :limit");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getEnquiryMonthlyTrend(PDO $conn, int $months = 6): array
{
    $stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
            DATE_FORMAT(created_at, '%b %Y') AS month_label,
            COUNT(*) AS count
        FROM enquiries
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
        GROUP BY month_key, month_label
        ORDER BY month_key ASC");
    $stmt->bindValue(':months', $months, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getPortfolioProjects(PDO $conn, bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM portfolio_projects';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, id ASC';

    return $conn->query($sql)->fetchAll();
}

function getPortfolioProjectById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM portfolio_projects WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function createPortfolioProject(PDO $conn, string $title, string $category, string $description, string $imageUrl, string $projectUrl, int $sortOrder = 0, bool $isActive = true): bool
{
    $stmt = $conn->prepare('INSERT INTO portfolio_projects (title, category, description, image_url, project_url, sort_order, is_active)
        VALUES (:title, :category, :description, :image_url, :project_url, :sort_order, :is_active)');

    return $stmt->execute([
        ':title' => $title,
        ':category' => $category,
        ':description' => $description,
        ':image_url' => $imageUrl,
        ':project_url' => $projectUrl,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
    ]);
}

function updatePortfolioProject(PDO $conn, int $id, string $title, string $category, string $description, string $imageUrl, string $projectUrl, int $sortOrder, bool $isActive): bool
{
    $stmt = $conn->prepare('UPDATE portfolio_projects SET title = :title, category = :category, description = :description,
        image_url = :image_url, project_url = :project_url, sort_order = :sort_order, is_active = :is_active WHERE id = :id');

    return $stmt->execute([
        ':title' => $title,
        ':category' => $category,
        ':description' => $description,
        ':image_url' => $imageUrl,
        ':project_url' => $projectUrl,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
        ':id' => $id,
    ]);
}

function deletePortfolioProject(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM portfolio_projects WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function getTrustedClients(PDO $conn, bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM trusted_clients';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY sort_order ASC, id ASC';

    return $conn->query($sql)->fetchAll();
}

function getTrustedClientById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM trusted_clients WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function createTrustedClient(PDO $conn, string $name, string $logoText, int $sortOrder = 0, bool $isActive = true): bool
{
    $stmt = $conn->prepare('INSERT INTO trusted_clients (name, logo_text, sort_order, is_active) VALUES (:name, :logo_text, :sort_order, :is_active)');

    return $stmt->execute([
        ':name' => $name,
        ':logo_text' => $logoText,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
    ]);
}

function updateTrustedClient(PDO $conn, int $id, string $name, string $logoText, int $sortOrder, bool $isActive): bool
{
    $stmt = $conn->prepare('UPDATE trusted_clients SET name = :name, logo_text = :logo_text, sort_order = :sort_order, is_active = :is_active WHERE id = :id');

    return $stmt->execute([
        ':name' => $name,
        ':logo_text' => $logoText,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive ? 1 : 0,
        ':id' => $id,
    ]);
}

function deleteTrustedClient(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM trusted_clients WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function buildWhatsAppUrl(string $phone, string $message): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if ($digits === '') {
        $digits = '919876543210';
    }

    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
}

function getWhatsAppQuickTemplates(): array
{
    try {
        $conn = getDbConnection();
        $rows = $conn->query('SELECT name, body FROM whatsapp_templates ORDER BY id ASC')->fetchAll();
        $templates = [];

        foreach ($rows as $row) {
            $templates[] = [
                'name' => $row['name'],
                'message' => $row['body'],
            ];
        }

        return $templates;
    } catch (Throwable $e) {
        return [];
    }
}

function applyMessageTemplate(string $body, array $enquiry, ?string $adminName = null): string
{
    $conn = getDbConnection();
    $sitePhone = getSetting($conn, 'site_phone', '+91 98765 43210');

    $replacements = [
        '{name}' => $enquiry['name'] ?? '',
        '{email}' => $enquiry['email'] ?? '',
        '{phone}' => $enquiry['phone'] ?? '',
        '{service}' => $enquiry['service'] ?? '',
        '{message}' => $enquiry['message'] ?? '',
        '{site_phone}' => $sitePhone ?? '',
        '{admin_name}' => $adminName ?? 'The Web Artist Team',
        '{{name}}' => $enquiry['name'] ?? '',
        '{{email}}' => $enquiry['email'] ?? '',
        '{{phone}}' => $enquiry['phone'] ?? '',
        '{{service}}' => $enquiry['service'] ?? '',
        '{{message}}' => $enquiry['message'] ?? '',
        '{{site_phone}}' => $sitePhone ?? '',
        '{{admin_name}}' => $adminName ?? 'The Web Artist Team',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $body);
}

function setEncryptedSetting(PDO $conn, string $key, string $value): void
{
    setSetting($conn, $key, $value === '' ? '' : twaEncryptSecret($value));
}

function getEncryptedSetting(PDO $conn, string $key, string $default = ''): string
{
    return twaDecryptSecret((string) getSetting($conn, $key, $default));
}

function normalizeSmtpHost(string $host): string
{
    $host = trim($host);

    if ($host === '') {
        return '';
    }

    $host = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $host);
    $host = preg_replace('~[/?#].*$~', '', $host);

    if (preg_match('#^(\[[^\]]+\]|[^:\s]+)(?::\d+)?$#', $host, $matches)) {
        $host = $matches[1];
    }

    $host = trim($host, '[]');

    if (!filter_var($host, FILTER_VALIDATE_IP)) {
        $host = strtolower($host);
    }

    return $host;
}

function validateSmtpHost(string $host): ?string
{
    $host = normalizeSmtpHost($host);

    if ($host === '') {
        return 'SMTP host is required when SMTP is enabled.';
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return null;
    }

    if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*)$/i', $host)) {
        return 'Invalid SMTP host. Use only the server name, e.g. smtp.gmail.com (no https://, slashes, or port in this field).';
    }

    $resolved = @gethostbyname($host);

    if ($resolved === $host) {
        return 'Cannot resolve SMTP host "' . $host . '". Check spelling and DNS. Use your provider\'s SMTP hostname (e.g. smtp.gmail.com, smtp.hostinger.com) — not your email address.';
    }

    return null;
}

function getWhatsAppTemplates(PDO $conn): array
{
    return $conn->query('SELECT * FROM whatsapp_templates ORDER BY id ASC')->fetchAll();
}

function getWhatsAppTemplateById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM whatsapp_templates WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function createWhatsAppTemplate(PDO $conn, string $name, string $body): bool
{
    $stmt = $conn->prepare('INSERT INTO whatsapp_templates (name, body) VALUES (:name, :body)');

    return $stmt->execute([':name' => $name, ':body' => $body]);
}

function updateWhatsAppTemplate(PDO $conn, int $id, string $name, string $body): bool
{
    $stmt = $conn->prepare('UPDATE whatsapp_templates SET name = :name, body = :body WHERE id = :id');

    return $stmt->execute([':name' => $name, ':body' => $body, ':id' => $id]);
}

function deleteWhatsAppTemplate(PDO $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM whatsapp_templates WHERE id = :id');

    return $stmt->execute([':id' => $id]);
}

function logEmailDelivery(PDO $conn, string $recipient, string $subject, string $context, bool $success, string $message = ''): void
{
    $stmt = $conn->prepare('INSERT INTO email_log (recipient, subject, context, status, message)
        VALUES (:recipient, :subject, :context, :status, :message)');
    $stmt->execute([
        ':recipient' => $recipient,
        ':subject' => $subject,
        ':context' => $context,
        ':status' => $success ? 'sent' : 'failed',
        ':message' => $message,
    ]);
}

function getEmailLog(PDO $conn, int $limit = 50, int $offset = 0): array
{
    $stmt = $conn->prepare('SELECT * FROM email_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function countEmailLog(PDO $conn): int
{
    return (int) $conn->query('SELECT COUNT(*) FROM email_log')->fetchColumn();
}

function countActivityLog(PDO $conn): int
{
    return (int) $conn->query('SELECT COUNT(*) FROM activity_log')->fetchColumn();
}

function countLoginHistory(PDO $conn): int
{
    return (int) $conn->query('SELECT COUNT(*) FROM login_history')->fetchColumn();
}

function adminMustChangePassword(PDO $conn): bool
{
    $userId = adminUserId();

    if (!$userId) {
        return false;
    }

    $stmt = $conn->prepare('SELECT force_password_change FROM admin_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    return $row && (int) ($row['force_password_change'] ?? 0) === 1;
}

function requirePasswordChangeIfNeeded(PDO $conn): void
{
    if (!adminMustChangePassword($conn)) {
        return;
    }

    $script = basename($_SERVER['PHP_SELF'] ?? '');
    $requestPath = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    $tab = $_GET['tab'] ?? '';
    $onPasswordSettings = ($script === 'settings.php' || $requestPath === 'settings') && $tab === 'password';

    if ($onPasswordSettings) {
        return;
    }

    if ($script === 'logout.php' || $requestPath === 'logout') {
        return;
    }

    flashMessage('warning', 'Please change your default password before continuing.');
    header('Location: settings.php?tab=password&required=1');
    exit;
}

function maybeRunScheduledBackup(PDO $conn): void
{
    if (getSetting($conn, 'backup_schedule_enabled', '0') !== '1') {
        return;
    }

    $days = max(1, (int) getSetting($conn, 'backup_schedule_days', '7'));
    $lastRun = trim((string) getSetting($conn, 'backup_last_run', ''));

    if ($lastRun !== '' && strtotime($lastRun) > strtotime('-' . $days . ' days')) {
        return;
    }

    $dir = dirname(__DIR__, 2) . '/storage/backups';

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $filename = $dir . '/auto-' . date('Y-m-d-His') . '.sql';
    $sql = generateDatabaseBackup($conn);

    if (@file_put_contents($filename, $sql) !== false) {
        setSetting($conn, 'backup_last_run', date('Y-m-d H:i:s'));
        logActivity($conn, 'auto_backup', 'system', null, basename($filename));
    }
}

function restoreDatabaseFromSql(PDO $conn, string $sql): array
{
    $sql = trim($sql);

    if ($sql === '') {
        return ['success' => false, 'message' => 'Backup file is empty.'];
    }

    if (stripos($sql, 'INSERT INTO') === false && stripos($sql, 'CREATE TABLE') === false) {
        return ['success' => false, 'message' => 'File does not look like a valid SQL backup.'];
    }

    try {
        $conn->exec('SET FOREIGN_KEY_CHECKS=0');
        $statements = preg_split('/;\s*\n/', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }

            $conn->exec($statement);
        }

        $conn->exec('SET FOREIGN_KEY_CHECKS=1');

        return ['success' => true, 'message' => 'Database restored successfully.'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()];
    }
}
