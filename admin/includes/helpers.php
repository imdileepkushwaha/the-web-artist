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

function updateAdminUserPassword(PDO $conn, int $userId, string $password): bool
{
    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');
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

function sendEnquiryNotificationEmail(array $enquiry): bool
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

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("UPDATE enquiries SET status = ? WHERE id IN ({$placeholders})");
    $stmt->execute(array_merge([$status], $ids));

    return $stmt->rowCount();
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

function createEmailTemplate(PDO $conn, string $name, string $subject, string $body): bool
{
    $stmt = $conn->prepare('INSERT INTO email_templates (name, subject, body) VALUES (:name, :subject, :body)');

    return $stmt->execute([
        ':name' => $name,
        ':subject' => $subject,
        ':body' => $body,
    ]);
}

function updateEmailTemplate(PDO $conn, int $id, string $name, string $subject, string $body): bool
{
    $stmt = $conn->prepare('UPDATE email_templates SET name = :name, subject = :subject, body = :body WHERE id = :id');

    return $stmt->execute([
        ':name' => $name,
        ':subject' => $subject,
        ':body' => $body,
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
    $tables = ['enquiries', 'enquiry_notes', 'admin_settings', 'admin_users', 'faq_items', 'testimonials', 'services', 'email_templates', 'activity_log', 'login_history', 'site_visits'];
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
    $stats['by_page'] = $pageStmt->fetchAll();

    return $stats;
}
