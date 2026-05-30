<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['results' => []]);
    exit;
}

checkSessionTimeout();

$q = trim($_GET['q'] ?? '');

if ($q === '' || strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$conn = getAdminDb();
$results = globalAdminSearch($conn, $q, 10);

echo json_encode(['results' => $results]);
