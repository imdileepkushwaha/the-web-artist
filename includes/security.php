<?php

function twaSessionCookiePath(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

    if (preg_match('#/admin(/|$)#', $script)) {
        $siteRoot = dirname(dirname($script));
    } else {
        $siteRoot = dirname($script);
    }

    if ($siteRoot === '/' || $siteRoot === '.' || $siteRoot === '') {
        return '/';
    }

    return rtrim($siteRoot, '/') . '/';
}

function twaEnsureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => twaSessionCookiePath(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

function twaEncryptionKey(): string
{
    static $key = null;

    if ($key !== null) {
        return $key;
    }

    $path = __DIR__ . '/../config/app.key';

    if (!is_file($path)) {
        $raw = bin2hex(random_bytes(32));
        @file_put_contents($path, $raw);
        @chmod($path, 0600);
    }

    $raw = trim((string) @file_get_contents($path));

    if ($raw === '') {
        $raw = hash('sha256', __DIR__ . php_uname());
    }

    return hash('sha256', $raw, true);
}

function twaEncryptSecret(string $plain): string
{
    if ($plain === '') {
        return '';
    }

    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', twaEncryptionKey(), OPENSSL_RAW_DATA, $iv);

    if ($cipher === false) {
        return $plain;
    }

    return 'enc:' . base64_encode($iv . $cipher);
}

function twaDecryptSecret(string $stored): string
{
    if ($stored === '' || !str_starts_with($stored, 'enc:')) {
        return $stored;
    }

    $payload = base64_decode(substr($stored, 4), true);

    if ($payload === false || strlen($payload) < 17) {
        return '';
    }

    $iv = substr($payload, 0, 16);
    $cipher = substr($payload, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', twaEncryptionKey(), OPENSSL_RAW_DATA, $iv);

    return $plain === false ? '' : $plain;
}

function csrfToken(): string
{
    twaEnsureSession();

    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken(?string $token = null): void
{
    twaEnsureSession();
    $token = $token ?? ($_POST['_csrf'] ?? '');

    if ($token === '' || !hash_equals((string) csrfToken(), (string) $token)) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }
}

function csrfValidateForPublicForm(): void
{
    twaEnsureSession();
    $token = $_POST['_csrf'] ?? '';

    if ($token === '' || !hash_equals((string) csrfToken(), (string) $token)) {
        throw new RuntimeException('Session expired. Please refresh the page and try again.');
    }
}

function honeypotField(string $name = 'website_url'): string
{
    return '<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">'
        . '<label for="hp_' . htmlspecialchars($name) . '">Leave blank</label>'
        . '<input type="text" id="hp_' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" tabindex="-1" autocomplete="off">'
        . '</div>';
}

function verifyHoneypot(string $name = 'website_url'): void
{
    if (trim($_POST[$name] ?? '') !== '') {
        http_response_code(400);
        exit('Submission rejected.');
    }
}

function twaRateLimitCheck(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
{
    twaEnsureSession();
    $bucketKey = '_rate_' . hash('sha256', $key);
    $now = time();
    $bucket = $_SESSION[$bucketKey] ?? ['count' => 0, 'start' => $now];

    if ($now - (int) $bucket['start'] > $windowSeconds) {
        $bucket = ['count' => 0, 'start' => $now];
    }

    if ((int) $bucket['count'] >= $maxAttempts) {
        return false;
    }

    $bucket['count'] = (int) $bucket['count'] + 1;
    $_SESSION[$bucketKey] = $bucket;

    return true;
}

function twaRateLimitRemainingSeconds(string $key, int $windowSeconds = 900): int
{
    twaEnsureSession();
    $bucketKey = '_rate_' . hash('sha256', $key);
    $bucket = $_SESSION[$bucketKey] ?? ['count' => 0, 'start' => time()];

    return max(0, $windowSeconds - (time() - (int) $bucket['start']));
}

function twaRateLimitReset(string $key): void
{
    twaEnsureSession();
    unset($_SESSION['_rate_' . hash('sha256', $key)]);
}

function twaClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
