<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

header('Content-Type: application/json');

$conn = getAdminDb();
$notifications = getAdminNotifications($conn, 8);
$count = getNewEnquiryNotificationCount($conn);

$items = array_map(static function (array $row): array {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'service' => $row['service'],
        'status' => $row['status'],
        'time_ago' => timeAgo($row['created_at']),
        'is_new' => $row['status'] === 'new',
    ];
}, $notifications);

echo json_encode([
    'count' => $count,
    'notifications' => $items,
]);
