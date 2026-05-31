<?php

require_once __DIR__ . '/../config.php';

twaEnsureSession();

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION[ADMIN_SESSION_KEY]);
}

function checkSessionTimeout(): void
{
    if (!isAdminLoggedIn()) {
        return;
    }

    $conn = getDbConnection();
    $timeoutMinutes = (int) (getSetting($conn, 'session_timeout_minutes', '30') ?: 30);
    $timeoutSeconds = max(300, $timeoutMinutes * 60);
    $lastActivity = $_SESSION['admin_last_activity'] ?? time();

    if (time() - $lastActivity > $timeoutSeconds) {
        logoutAdmin();
        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['admin_last_activity'] = time();
}

function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    checkSessionTimeout();

    if (function_exists('requirePasswordChangeIfNeeded')) {
        requirePasswordChangeIfNeeded(getDbConnection());
    }
}

function redirectIfLoggedIn(): void
{
    if (isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function loginAdmin(string $username, string $password): bool
{
    $conn = getDbConnection();
    $ip = twaClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $rateKey = 'login:' . $ip . ':' . strtolower($username);

    if (!twaRateLimitCheck($rateKey, 8, 900)) {
        recordLoginAttempt($conn, null, $username, false, $ip, $userAgent);

        return false;
    }

    $stmt = $conn->prepare('SELECT * FROM admin_users WHERE username = :username AND is_active = 1 LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        twaRateLimitReset($rateKey);
        $_SESSION[ADMIN_SESSION_KEY] = true;
        $_SESSION[ADMIN_SESSION_USER] = $user['username'];
        $_SESSION[ADMIN_SESSION_USER_ID] = (int) $user['id'];
        $_SESSION[ADMIN_SESSION_ROLE] = $user['role'];
        $_SESSION['admin_display_name'] = $user['name'] ?: $user['username'];
        $_SESSION['admin_last_activity'] = time();

        $conn->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id')->execute([':id' => $user['id']]);
        recordLoginAttempt($conn, (int) $user['id'], $username, true, $ip, $userAgent);
        logActivity($conn, 'login', 'user', (int) $user['id'], 'Successful login');

        return true;
    }

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD && defined('TWA_ALLOW_CONFIG_LOGIN') && TWA_ALLOW_CONFIG_LOGIN) {
        twaRateLimitReset($rateKey);
        $fallbackUser = getAdminUserAccount($conn, $username);

        $_SESSION[ADMIN_SESSION_KEY] = true;
        $_SESSION[ADMIN_SESSION_USER] = $username;
        $_SESSION[ADMIN_SESSION_USER_ID] = $fallbackUser ? (int) $fallbackUser['id'] : 0;
        $_SESSION[ADMIN_SESSION_ROLE] = $fallbackUser['role'] ?? 'admin';
        $_SESSION['admin_display_name'] = $fallbackUser['name'] ?? $username;
        $_SESSION['admin_last_activity'] = time();
        recordLoginAttempt($conn, $fallbackUser ? (int) $fallbackUser['id'] : null, $username, true, $ip, $userAgent);

        return true;
    }

    recordLoginAttempt($conn, $user ? (int) $user['id'] : null, $username, false, $ip, $userAgent);

    return false;
}

function logoutAdmin(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function adminUser(): string
{
    return $_SESSION[ADMIN_SESSION_USER] ?? ADMIN_USERNAME;
}

function adminUserId(): ?int
{
    $id = $_SESSION[ADMIN_SESSION_USER_ID] ?? null;
    return $id !== null ? (int) $id : null;
}

function adminDisplayName(): string
{
    return $_SESSION['admin_display_name'] ?? adminUser();
}

function enquirySources(): array
{
    return [
        'hero' => 'Hero Form',
        'contact' => 'Contact Form',
    ];
}

function sourceBadgeClass(string $source): string
{
    return $source === 'hero' ? 'badge-hero' : 'badge-contact';
}

function getAdminDb(): PDO
{
    $conn = getDbConnection();
    ensureEnquiriesTable($conn);
    return $conn;
}

function enquiryStatuses(): array
{
    return [
        'new' => 'New',
        'read' => 'Read',
        'contacted' => 'Contacted',
        'closed' => 'Closed',
    ];
}

function formatEnquiryDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    return date('d M Y, h:i A', strtotime($date));
}

function statusBadgeClass(string $status): string
{
    return match ($status) {
        'new' => 'badge-new',
        'read' => 'badge-read',
        'contacted' => 'badge-contacted',
        'closed' => 'badge-closed',
        default => 'badge-read',
    };
}

function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getDashboardStats(PDO $conn): array
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

    $row = $conn->query('SELECT COUNT(*) AS total FROM enquiries')->fetch();
    $stats['total'] = (int) ($row['total'] ?? 0);

    $statusRows = $conn->query("SELECT status, COUNT(*) AS count FROM enquiries GROUP BY status")->fetchAll();
    foreach ($statusRows as $statusRow) {
        $stats[$statusRow['status']] = (int) $statusRow['count'];
    }

    $todayRow = $conn->query('SELECT COUNT(*) AS count FROM enquiries WHERE DATE(created_at) = CURDATE()')->fetch();
    $stats['today'] = (int) ($todayRow['count'] ?? 0);

    $weekRow = $conn->query('SELECT COUNT(*) AS count FROM enquiries WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)')->fetch();
    $stats['week'] = (int) ($weekRow['count'] ?? 0);

    return $stats;
}

function getServiceBreakdown(PDO $conn, int $limit = 5): array
{
    $stmt = $conn->prepare('SELECT service, COUNT(*) AS count FROM enquiries GROUP BY service ORDER BY count DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRecentEnquiries(PDO $conn, int $limit = 5): array
{
    $stmt = $conn->prepare('SELECT * FROM enquiries ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getNewEnquiryNotificationCount(PDO $conn): int
{
    $row = $conn->query("SELECT COUNT(*) AS count FROM enquiries WHERE status = 'new'")->fetch();
    return (int) ($row['count'] ?? 0);
}

function getAdminNotifications(PDO $conn, int $limit = 8): array
{
    $stmt = $conn->prepare('SELECT id, name, service, status, created_at FROM enquiries ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function timeAgo(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    }

    if ($diff < 3600) {
        $mins = (int) floor($diff / 60);
        return $mins . ' min' . ($mins === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 604800) {
        $days = (int) floor($diff / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('d M Y', $timestamp);
}

function getEnquiries(PDO $conn, array $filters = [], int $page = 1, int $perPage = ENQUIRIES_PER_PAGE): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status']) && array_key_exists($filters['status'], enquiryStatuses())) {
        $where[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(name LIKE :search OR email LIKE :search OR phone LIKE :search OR service LIKE :search OR message LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['follow_up'])) {
        if ($filters['follow_up'] === 'due') {
            $where[] = "follow_up_date IS NOT NULL AND follow_up_date <= CURDATE() AND status NOT IN ('closed')";
        } elseif ($filters['follow_up'] === 'overdue') {
            $where[] = "follow_up_date IS NOT NULL AND follow_up_date < CURDATE() AND status NOT IN ('closed')";
        } elseif ($filters['follow_up'] === 'today') {
            $where[] = "follow_up_date = CURDATE() AND status NOT IN ('closed')";
        }
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM enquiries $whereSql");
    $countStmt->execute($params);
    $total = (int) ($countStmt->fetch()['total'] ?? 0);

    $offset = max(0, ($page - 1) * $perPage);

    $stmt = $conn->prepare("SELECT * FROM enquiries $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => max(1, (int) ceil($total / $perPage)),
    ];
}

function getEnquiryById(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM enquiries WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $enquiry = $stmt->fetch();
    return $enquiry ?: null;
}

function updateEnquiry(PDO $conn, int $id, string $status): bool
{
    if (!array_key_exists($status, enquiryStatuses())) {
        return false;
    }

    $stmt = $conn->prepare('UPDATE enquiries SET status = :status WHERE id = :id');
    return $stmt->execute([
        ':status' => $status,
        ':id' => $id,
    ]);
}

function getEnquiryNotes(PDO $conn, int $enquiryId): array
{
    $stmt = $conn->prepare('SELECT * FROM enquiry_notes WHERE enquiry_id = :enquiry_id ORDER BY created_at DESC');
    $stmt->execute([':enquiry_id' => $enquiryId]);
    return $stmt->fetchAll();
}

function addEnquiryNote(PDO $conn, int $enquiryId, string $note, string $createdBy): bool
{
    $note = trim($note);

    if ($note === '') {
        return false;
    }

    $stmt = $conn->prepare('INSERT INTO enquiry_notes (enquiry_id, note, created_by) VALUES (:enquiry_id, :note, :created_by)');
    $inserted = $stmt->execute([
        ':enquiry_id' => $enquiryId,
        ':note' => $note,
        ':created_by' => $createdBy,
    ]);

    if ($inserted) {
        $conn->prepare('UPDATE enquiries SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $enquiryId]);
    }

    return $inserted;
}

function deleteEnquiry(PDO $conn, int $id): bool
{
    $conn->prepare('DELETE FROM enquiry_notes WHERE enquiry_id = :id')->execute([':id' => $id]);
    $stmt = $conn->prepare('DELETE FROM enquiries WHERE id = :id');
    return $stmt->execute([':id' => $id]);
}

function markEnquiryAsRead(PDO $conn, int $id): void
{
    $stmt = $conn->prepare("UPDATE enquiries SET status = 'read' WHERE id = :id AND status = 'new'");
    $stmt->execute([':id' => $id]);
}

function activeNav(string $page): string
{
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $page ? 'active' : '';
}

function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper($part[0]);
        }
    }

    return $initials !== '' ? $initials : '?';
}

function formatEnquiryDateShort(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');

    if ($timestamp >= $today) {
        return 'Today, ' . date('h:i A', $timestamp);
    }

    if ($timestamp >= $yesterday) {
        return 'Yesterday, ' . date('h:i A', $timestamp);
    }

    return date('d M Y', $timestamp);
}
