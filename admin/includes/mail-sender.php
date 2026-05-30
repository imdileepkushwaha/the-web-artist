<?php

function sendSystemEmail(string $to, string $subject, string $body, ?string $replyTo = null): array
{
    $to = trim($to);

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid recipient email is required.'];
    }

    $conn = getDbConnection();
    $fromEmail = trim((string) getSetting($conn, 'admin_email', ''));
    $fromName = trim((string) getSetting($conn, 'email_from_name', ''));

    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Configure a valid sender email in Email Setup first.'];
    }

    if ($fromName === '') {
        $fromName = 'The Web Artist';
    }

    if (getSetting($conn, 'smtp_enabled', '0') === '1') {
        return sendSmtpEmail($to, $subject, $body, $fromEmail, $fromName, $replyTo, $conn);
    }

    return sendPhpMailEmail($to, $subject, $body, $fromEmail, $fromName, $replyTo);
}

function sendPhpMailEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName, ?string $replyTo): array
{
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $headers = "From: {$encodedFromName} <{$fromEmail}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: {$replyTo}\r\n";
    }

    $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

    if (!$sent) {
        return [
            'success' => false,
            'message' => 'PHP mail() failed. Enable SMTP in Email Setup or check your server mail configuration.',
        ];
    }

    return ['success' => true, 'message' => 'Email sent successfully via PHP mail().'];
}

function sendSmtpEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName, ?string $replyTo, PDO $conn): array
{
    $host = trim((string) getSetting($conn, 'smtp_host', ''));
    $port = (int) getSetting($conn, 'smtp_port', '587');
    $encryption = strtolower(trim((string) getSetting($conn, 'smtp_encryption', 'tls')));
    $username = trim((string) getSetting($conn, 'smtp_username', ''));
    $password = (string) getSetting($conn, 'smtp_password', '');

    if ($host === '') {
        return ['success' => false, 'message' => 'SMTP host is required when SMTP is enabled.'];
    }

    if ($port <= 0) {
        $port = 587;
    }

    $remote = $host . ':' . $port;
    $transport = ($encryption === 'ssl') ? 'ssl://' . $remote : $remote;

    $socket = @stream_socket_client(
        $transport,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]])
    );

    if (!$socket) {
        return ['success' => false, 'message' => "Could not connect to SMTP server: {$errstr} ({$errno})"];
    }

    stream_set_timeout($socket, 20);

    try {
        smtpExpect($socket, [220]);
        smtpCommand($socket, 'EHLO localhost', [250]);

        if ($encryption === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to enable TLS encryption.');
            }

            smtpCommand($socket, 'EHLO localhost', [250]);
        }

        if ($username !== '') {
            smtpCommand($socket, 'AUTH LOGIN', [334]);
            smtpCommand($socket, base64_encode($username), [334]);
            smtpCommand($socket, base64_encode($password), [235]);
        }

        smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);

        $message = buildSmtpMessage($fromEmail, $fromName, $to, $subject, $body, $replyTo);
        fwrite($socket, $message . "\r\n.\r\n");
        smtpExpect($socket, [250]);
        smtpCommand($socket, 'QUIT', [221]);
    } catch (Throwable $e) {
        fclose($socket);

        return ['success' => false, 'message' => 'SMTP error: ' . $e->getMessage()];
    }

    fclose($socket);

    return ['success' => true, 'message' => 'Email sent successfully via SMTP.'];
}

function buildSmtpMessage(string $fromEmail, string $fromName, string $to, string $subject, string $body, ?string $replyTo): string
{
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $date = gmdate('D, d M Y H:i:s') . ' +0000';
    $messageId = '<' . bin2hex(random_bytes(8)) . '@thewebartist.local>';

    $lines = [
        'Date: ' . $date,
        'Message-ID: ' . $messageId,
        'From: ' . $encodedFromName . ' <' . $fromEmail . '>',
        'To: <' . $to . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $lines[] = 'Reply-To: ' . $replyTo;
    }

    $lines[] = '';
    $lines[] = str_replace(["\r\n", "\r"], "\n", $body);

    return implode("\r\n", $lines);
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

function sendEnquiryNotificationIfEnabled(array $enquiry): bool
{
    $conn = getDbConnection();

    if (getSetting($conn, 'notify_enquiries_enabled', '1') !== '1') {
        return false;
    }

    $notifyEmail = trim((string) getSetting($conn, 'notify_email', ''));

    if ($notifyEmail === '') {
        return false;
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

    $result = sendSystemEmail(
        $notifyEmail,
        $subject,
        $body,
        $enquiry['email'] ?? null
    );

    return $result['success'];
}
