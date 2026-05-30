<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();

$conn = getAdminDb();

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
    'from' => trim($_GET['from'] ?? ''),
    'to' => trim($_GET['to'] ?? ''),
];

$csv = exportEnquiriesCsv($conn, $filters);
$filename = 'enquiries-' . date('Y-m-d-His') . '.csv';

logActivity($conn, 'export_csv', 'enquiries', null, 'Exported enquiries CSV');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv;
exit;
