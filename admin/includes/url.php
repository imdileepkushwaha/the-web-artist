<?php

function adminBasePath(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    return $base === '' ? '/admin' : $base;
}

function adminUrl(string $page = '', array $query = []): string
{
    $base = adminBasePath();
    $page = ltrim($page, '/');
    $page = preg_replace('/\.php$/', '', $page);

    if ($page === '' || $page === 'index') {
        $url = $base . '/';
    } else {
        $url = $base . '/' . $page;
    }

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function siteHomeUrl(): string
{
    $adminBase = adminBasePath();
    $siteBase = dirname($adminBase);

    return $siteBase === '/' || $siteBase === '.' ? '/' : $siteBase . '/';
}
