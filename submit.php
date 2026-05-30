<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/admin/includes/helpers.php';

try {
    $conn = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? 'General Enquiry');
    $message = trim($_POST['message'] ?? '');
    $source = in_array($_POST['source'] ?? '', ['hero', 'contact'], true) ? $_POST['source'] : 'contact';

    if ($name === '' || $email === '' || $phone === '') {
        throw new RuntimeException('Please fill in all required fields.');
    }

    $stmt = $conn->prepare('INSERT INTO enquiries (name, email, phone, service, message, status, source)
                            VALUES (:name, :email, :phone, :service, :message, :status, :source)');

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':service' => $service,
        ':message' => $message,
        ':status' => 'new',
        ':source' => $source,
    ]);

    $enquiryId = (int) $conn->lastInsertId();
    $enquiry = [
        'id' => $enquiryId,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'message' => $message,
        'source' => $source,
    ];

    sendEnquiryNotificationEmail($enquiry);

    header('Location: thank-you.php');
    exit;
} catch (Throwable $e) {