<?php

function siteBasePath(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if ($base === '/' || $base === '.') {
        $base = '';
    }

    return $base;
}

function siteUrl(string $page = '', array $query = []): string
{
    $base = siteBasePath();
    $page = ltrim($page, '/');
    $page = preg_replace('/\.php$/', '', $page);

    if ($page === '' || $page === 'index') {
        $url = ($base !== '' ? $base : '') . '/';
    } else {
        $url = ($base !== '' ? $base : '') . '/' . $page;
    }

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}
