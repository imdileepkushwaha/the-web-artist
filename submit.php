<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/admin/includes/helpers.php';

try {
    $conn = getDbConnection();
    twaEnsureSession();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php');
        exit;
    }

    csrfValidateForPublicForm();
    verifyHoneypot('website_url');
    verifyHoneypot('website_url_contact');

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? 'General Enquiry');
    $message = trim($_POST['message'] ?? '');
    $source = in_array($_POST['source'] ?? '', ['hero', 'contact'], true) ? $_POST['source'] : 'contact';

    if ($name === '' || $email === '' || $phone === '') {
        throw new RuntimeException('Please fill in all required fields.');
    }

    if ($service === '') {
        throw new RuntimeException('Please select a service.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    $ipKey = 'enquiry:' . twaClientIp();

    if (!twaRateLimitCheck($ipKey, 10, 3600)) {
        throw new RuntimeException('Too many submissions. Please try again later.');
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

    try {
        sendEnquiryNotificationEmail($enquiry);
    } catch (Throwable $mailError) {
        // Enquiry is saved even if notification email fails.
    }

    header('Location: thank-you.php');
    exit;
} catch (Throwable $e) {
    twaEnsureSession();
    $_SESSION['form_error'] = $e->getMessage();
    $_SESSION['form_error_source'] = ($_POST['source'] ?? '') === 'hero' ? 'hero' : 'contact';
    $anchor = ($_POST['source'] ?? '') === 'hero' ? '#home' : '#contact';
    header('Location: index.php' . $anchor);
    exit;
}
