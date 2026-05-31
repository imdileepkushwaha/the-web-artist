<?php

function sendSystemEmail(string $to, string $subject, string $body, ?string $replyTo = null, string $context = 'system', array $attachments = []): array
{
    $to = trim($to);

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid recipient email is required.'];
    }

    $conn = getDbConnection();
    $fromEmail = trim((string) getSetting($conn, 'admin_email', ''));
    $fromName = trim((string) getSetting($conn, 'email_from_name', ''));

    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Configure a valid sender email in Site settings (admin_email) first.'];
    }

    if ($fromName === '') {
        $fromName = 'The Web Artist';
    }

    if (getSetting($conn, 'smtp_enabled', '0') === '1') {
        $result = sendSmtpEmail($to, $subject, $body, $fromEmail, $fromName, $replyTo, $conn, $attachments);
    } else {
        $result = sendPhpMailEmail($to, $subject, $body, $fromEmail, $fromName, $replyTo, $attachments);
    }

    if (function_exists('logEmailDelivery')) {
        try {
            logEmailDelivery($conn, $to, $subject, $context, $result['success'], $result['message'] ?? '');
        } catch (Throwable $e) {
            // Do not block sending if logging fails.
        }
    }

    return $result;
}

function sendPhpMailEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName, ?string $replyTo, array $attachments = []): array
{
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $mime = buildMimeEmailParts($body, $attachments);
    $headers = "From: {$encodedFromName} <{$fromEmail}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= $mime['content_type'] . "\r\n";

    if ($mime['transfer_encoding'] !== null) {
        $headers .= $mime['transfer_encoding'] . "\r\n";
    }

    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: {$replyTo}\r\n";
    }

    $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $mime['body'], $headers);

    if (!$sent) {
        return [
            'success' => false,
            'message' => 'PHP mail() failed. Enable SMTP in Email Setup or check your server mail configuration.',
        ];
    }

    return ['success' => true, 'message' => 'Email sent successfully via PHP mail().'];
}

function smtpClientHelo(PDO $conn): string
{
    $from = trim((string) getSetting($conn, 'admin_email', ''));

    if ($from !== '' && str_contains($from, '@')) {
        $domain = strtolower(substr($from, strrpos($from, '@') + 1));

        if ($domain !== '' && str_contains($domain, '.')) {
            return $domain;
        }
    }

    $host = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    return $host !== '' ? $host : 'localhost';
}

function smtpSslOptions(PDO $conn): array
{
    $skipVerify = getSetting($conn, 'smtp_skip_ssl_verify', '0') === '1'
        || (defined('DB_ENV') && DB_ENV === 'local');

    return [
        'verify_peer' => !$skipVerify,
        'verify_peer_name' => !$skipVerify,
        'allow_self_signed' => $skipVerify,
    ];
}

function sendSmtpEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName, ?string $replyTo, PDO $conn, array $attachments = []): array
{
    $host = normalizeSmtpHost((string) getSetting($conn, 'smtp_host', ''));
    $port = (int) getSetting($conn, 'smtp_port', '587');
    $encryption = strtolower(trim((string) getSetting($conn, 'smtp_encryption', 'tls')));
    $username = trim((string) getSetting($conn, 'smtp_username', ''));
    $password = smtpPasswordPlain($conn);

    if ($host === '') {
        return ['success' => false, 'message' => 'SMTP host is required when SMTP is enabled.'];
    }

    $hostError = validateSmtpHost($host);

    if ($hostError !== null) {
        return ['success' => false, 'message' => $hostError];
    }

    if ($port <= 0) {
        $port = 587;
    }

    if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
        $encryption = 'tls';
    }

    if ($encryption === 'tls' && $port === 465) {
        $encryption = 'ssl';
    }

    if ($encryption === 'ssl' && $port === 587) {
        $port = 465;
    }

    if ($username === '') {
        return ['success' => false, 'message' => 'SMTP username is required (usually your full email address).'];
    }

    if ($password === '') {
        return [
            'success' => false,
            'message' => 'SMTP password is missing or could not be decrypted. Re-enter the password in Email Setup and save on this server.',
        ];
    }

    $remote = $host . ':' . $port;
    $transport = ($encryption === 'ssl') ? 'ssl://' . $remote : $remote;
    $helo = smtpClientHelo($conn);
    $envelopeFrom = filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : $fromEmail;

    $socket = @stream_socket_client(
        $transport,
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => smtpSslOptions($conn)])
    );

    if (!$socket) {
        $hint = str_contains(strtolower($errstr), 'getaddrinfo')
            ? ' DNS could not resolve "' . $host . '". Use hostname only, e.g. smtp.gmail.com.'
            : '';

        return ['success' => false, 'message' => "Could not connect to SMTP server ({$host}:{$port}): {$errstr} ({$errno}).{$hint}"];
    }

    stream_set_timeout($socket, 30);

    try {
        smtpExpect($socket, [220]);
        smtpCommand($socket, 'EHLO ' . $helo, [250]);

        if ($encryption === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;

            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }

            if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new RuntimeException('Unable to enable TLS encryption. Try SSL on port 465, or enable "Relaxed SSL" for shared hosting.');
            }

            smtpCommand($socket, 'EHLO ' . $helo, [250]);
        }

        smtpCommand($socket, 'AUTH LOGIN', [334]);
        smtpCommand($socket, base64_encode($username), [334]);
        smtpCommand($socket, base64_encode($password), [235]);

        smtpCommand($socket, 'MAIL FROM:<' . $envelopeFrom . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);

        $message = buildSmtpMessage($fromEmail, $fromName, $to, $subject, $body, $replyTo, $attachments);
        fwrite($socket, $message . "\r\n.\r\n");
        smtpExpect($socket, [250]);
        smtpCommand($socket, 'QUIT', [221]);
    } catch (Throwable $e) {
        fclose($socket);

        return ['success' => false, 'message' => 'SMTP error: ' . $e->getMessage()];
    }

    fclose($socket);

    return ['success' => true, 'message' => 'Email sent successfully via SMTP (' . $host . ':' . $port . ', ' . strtoupper($encryption) . ').'];
}

function buildSmtpMessage(string $fromEmail, string $fromName, string $to, string $subject, string $body, ?string $replyTo, array $attachments = []): string
{
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $date = gmdate('D, d M Y H:i:s') . ' +0000';
    $messageId = '<' . bin2hex(random_bytes(8)) . '@' . preg_replace('/[^a-z0-9.-]/i', '', smtpClientHelo(getDbConnection())) . '>';
    $mime = buildMimeEmailParts($body, $attachments);

    $lines = [
        'Date: ' . $date,
        'Message-ID: ' . $messageId,
        'From: ' . $encodedFromName . ' <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        $mime['content_type'],
    ];

    if ($mime['transfer_encoding'] !== null) {
        $lines[] = $mime['transfer_encoding'];
    }

    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $lines[] = 'Reply-To: ' . $replyTo;
    }

    $lines[] = '';
    $lines[] = $mime['body'];

    return implode("\r\n", $lines);
}

function encodeMimeFilename(string $filename): string
{
    if (preg_match('/^[\x20-\x7E]+$/', $filename)) {
        return $filename;
    }

    return '=?UTF-8?B?' . base64_encode($filename) . '?=';
}

function buildMimeEmailParts(string $body, array $attachments = []): array
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);

    if (empty($attachments)) {
        return [
            'content_type' => 'Content-Type: text/plain; charset=UTF-8',
            'transfer_encoding' => 'Content-Transfer-Encoding: 8bit',
            'body' => $body,
        ];
    }

    $boundary = '----=_TWA_' . bin2hex(random_bytes(12));
    $parts = [
        '--' . $boundary,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        '',
        $body,
    ];

    foreach ($attachments as $attachment) {
        $filename = encodeMimeFilename((string) ($attachment['name'] ?? 'attachment'));
        $mimeType = (string) ($attachment['mime'] ?? 'application/octet-stream');
        $path = (string) ($attachment['path'] ?? '');

        if ($path === '' || !is_readable($path)) {
            continue;
        }

        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: ' . $mimeType . '; name="' . $filename . '"';
        $parts[] = 'Content-Transfer-Encoding: base64';
        $parts[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
        $parts[] = '';
        $parts[] = chunk_split(base64_encode((string) file_get_contents($path)), 76, "\r\n");
    }

    $parts[] = '--' . $boundary . '--';

    return [
        'content_type' => 'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        'transfer_encoding' => null,
        'body' => implode("\r\n", $parts),
    ];
}

function smtpCommand($socket, string $command, array $expectedCodes): void
{
    fwrite($socket, $command . "\r\n");
    smtpExpect($socket, $expectedCodes);
}

function smtpExpect($socket, array $expectedCodes): void
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr(trim($response), 0, 3);

    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException(trim($response) ?: 'Unexpected SMTP response.');
    }
}

function smtpPasswordPlain(PDO $conn): string
{
    if (!function_exists('getEncryptedSetting')) {
        return trim((string) getSetting($conn, 'smtp_password', ''));
    }

    return trim(getEncryptedSetting($conn, 'smtp_password', ''));
}

function smtpPasswordIsConfigured(PDO $conn): bool
{
    $stored = trim((string) getSetting($conn, 'smtp_password', ''));

    if ($stored === '') {
        return false;
    }

    if (str_starts_with($stored, 'enc:')) {
        return smtpPasswordPlain($conn) !== '';
    }

    return true;
}

function getEmailSetupDiagnostics(PDO $conn): array
{
    $checks = [];
    $adminEmail = trim((string) getSetting($conn, 'admin_email', ''));
    $notifyEmail = trim((string) getSetting($conn, 'notify_email', ''));
    $smtpEnabled = getSetting($conn, 'smtp_enabled', '0') === '1';
    $host = normalizeSmtpHost((string) getSetting($conn, 'smtp_host', ''));
    $hostError = $host !== '' ? validateSmtpHost($host) : 'SMTP host is empty.';

    $checks[] = [
        'label' => 'Sender email (Site settings)',
        'ok' => (bool) filter_var($adminEmail, FILTER_VALIDATE_EMAIL),
        'detail' => $adminEmail !== '' ? $adminEmail : 'Not set — required for all outgoing mail',
    ];
    $checks[] = [
        'label' => 'Notification email (Site settings)',
        'ok' => (bool) filter_var($notifyEmail, FILTER_VALIDATE_EMAIL),
        'detail' => $notifyEmail !== '' ? $notifyEmail : 'Not set — enquiry alerts will not send',
    ];
    $checks[] = [
        'label' => 'Enquiry email alerts',
        'ok' => getSetting($conn, 'notify_enquiries_enabled', '1') === '1',
        'detail' => getSetting($conn, 'notify_enquiries_enabled', '1') === '1' ? 'Enabled' : 'Disabled in Site settings',
    ];

    if ($smtpEnabled) {
        $checks[] = [
            'label' => 'SMTP host',
            'ok' => $host !== '' && $hostError === null,
            'detail' => $host !== '' ? ($hostError ?? $host) : 'Missing',
        ];
        $checks[] = [
            'label' => 'SMTP username',
            'ok' => trim((string) getSetting($conn, 'smtp_username', '')) !== '',
            'detail' => trim((string) getSetting($conn, 'smtp_username', '')) ?: 'Missing — use full email address',
        ];
        $checks[] = [
            'label' => 'SMTP password',
            'ok' => smtpPasswordIsConfigured($conn),
            'detail' => smtpPasswordIsConfigured($conn)
                ? 'Configured'
                : 'Missing or cannot decrypt — re-save password on this server after deploy',
        ];
        $checks[] = [
            'label' => 'SMTP port / encryption',
            'ok' => true,
            'detail' => getSetting($conn, 'smtp_port', '587') . ' / ' . strtoupper((string) getSetting($conn, 'smtp_encryption', 'tls')),
        ];
    } else {
        $checks[] = [
            'label' => 'Delivery method',
            'ok' => true,
            'detail' => 'PHP mail() — enable SMTP on live servers for reliable delivery',
        ];
    }

    return $checks;
}

function runAllEmailFlowTests(PDO $conn): array
{
    $notifyEmail = trim((string) getSetting($conn, 'notify_email', ''));

    if ($notifyEmail === '') {
        $notifyEmail = trim((string) getSetting($conn, 'admin_email', ''));
    }

    if ($notifyEmail === '') {
        return [
            'config' => ['success' => false, 'message' => 'Set notification or sender email in Site settings first.'],
        ];
    }

    $timestamp = date('Y-m-d H:i:s');
    $results = [];

    $results['test_email'] = sendSystemEmail(
        $notifyEmail,
        'Test 1/3 — Admin test email',
        "This is a test email from Email Setup.\nSent at: {$timestamp}\nContext: test_email",
        null,
        'test_email'
    );

    $results['enquiry_notification'] = sendSystemEmail(
        $notifyEmail,
        'Test 2/3 — New enquiry alert',
        "Simulated enquiry notification.\n\nName: Test User\nEmail: test@example.com\nPhone: 9999999999\nService: Web Development\nSource: test\nMessage:\nThis is a test enquiry alert.\n\nSent at: {$timestamp}",
        'test@example.com',
        'enquiry_notification'
    );

    $results['follow_up_reminder'] = sendSystemEmail(
        $notifyEmail,
        'Test 3/3 — Follow-up reminder',
        "Simulated follow-up reminder.\n\nYou have 2 enquiry/enquiries with follow-ups due.\n\nSent at: {$timestamp}",
        null,
        'follow_up_reminder'
    );

    return $results;
}

function sendEnquiryNotificationIfEnabled(array $enquiry): array
{
    $conn = getDbConnection();

    if (getSetting($conn, 'notify_enquiries_enabled', '1') !== '1') {
        return ['success' => false, 'message' => 'Enquiry email alerts are disabled in Site settings.'];
    }

    $notifyEmail = trim((string) getSetting($conn, 'notify_email', ''));

    if ($notifyEmail === '') {
        $notifyEmail = trim((string) getSetting($conn, 'admin_email', ''));
    }

    if ($notifyEmail === '') {
        return ['success' => false, 'message' => 'Notification email is not configured in Site settings.'];
    }

    $subject = 'New Enquiry: ' . ($enquiry['name'] ?? 'Unknown');
    $source = $enquiry['source'] ?? 'contact';
    $body = "A new enquiry has been submitted.\n\n";
    $body .= 'Name: ' . ($enquiry['name'] ?? '') . "\n";
    $body .= 'Email: ' . ($enquiry['email'] ?? '') . "\n";
    $body .= 'Phone: ' . ($enquiry['phone'] ?? '') . "\n";
    $body .= 'Service: ' . ($enquiry['service'] ?? '') . "\n";
    $body .= 'Source: ' . $source . "\n";
    $body .= "Message:\n" . ($enquiry['message'] ?? '') . "\n";
    $body .= "\nView in admin: enquiry.php?id=" . (int) ($enquiry['id'] ?? 0) . "\n";

    return sendSystemEmail(
        $notifyEmail,
        $subject,
        $body,
        $enquiry['email'] ?? null,
        'enquiry_notification'
    );
}
