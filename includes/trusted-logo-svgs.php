<?php

function trustedClientLogoKey(string $logoText, string $name): string
{
    $key = strtolower(trim($logoText));

    if ($key !== '') {
        return preg_replace('/[^a-z0-9_-]/', '', $key) ?: 'custom';
    }

    return preg_replace('/[^a-z0-9_-]/', '', strtolower($name)) ?: 'custom';
}

function trustedClientLogoSvg(string $key, int $uid = 0): ?string
{
    $uid = max(0, $uid);
    $grad = static fn(int $n) => 'grad' . $n . '-' . $uid;

    $logos = [
        'technova' => '<svg viewBox="0 0 135 30" height="26" class="trusted-logo" aria-label="TechNova" role="img">
            <defs>
                <linearGradient id="' . $grad(1) . '" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#4F46E5;stop-opacity:1"></stop>
                    <stop offset="100%" style="stop-color:#9333EA;stop-opacity:1"></stop>
                </linearGradient>
            </defs>
            <polygon points="5,25 15,5 25,25" fill="none" stroke="url(#' . $grad(1) . ')" stroke-width="4" stroke-linejoin="round"></polygon>
            <text x="35" y="22" fill="#1e293b" font-family="Inter, sans-serif" font-weight="800" font-size="18" letter-spacing="-0.5">TechNova</text>
        </svg>',
        'healthsync' => '<svg viewBox="0 0 145 30" height="26" class="trusted-logo" aria-label="HealthSync" role="img">
            <defs>
                <linearGradient id="' . $grad(2) . '" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#10B981;stop-opacity:1"></stop>
                    <stop offset="100%" style="stop-color:#06B6D4;stop-opacity:1"></stop>
                </linearGradient>
            </defs>
            <path d="M15 5a10 10 0 00-10 10 10 10 0 0020 0 10 10 0 00-10-10z" fill="none" stroke="url(#' . $grad(2) . ')" stroke-width="3.5"></path>
            <path d="M15 10v10M10 15h10" stroke="url(#' . $grad(2) . ')" stroke-width="3.5" stroke-linecap="round"></path>
            <text x="35" y="22" fill="#1e293b" font-family="Inter, sans-serif" font-weight="800" font-size="18" letter-spacing="-0.5">HealthSync</text>
        </svg>',
        'educore' => '<svg viewBox="0 0 135 30" height="26" class="trusted-logo" aria-label="EduCore" role="img">
            <defs>
                <linearGradient id="' . $grad(3) . '" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#F59E0B;stop-opacity:1"></stop>
                    <stop offset="100%" style="stop-color:#EF4444;stop-opacity:1"></stop>
                </linearGradient>
            </defs>
            <rect x="5" y="5" width="20" height="20" rx="6" fill="none" stroke="url(#' . $grad(3) . ')" stroke-width="3.5"></rect>
            <circle cx="15" cy="15" r="4" fill="url(#' . $grad(3) . ')"></circle>
            <text x="35" y="22" fill="#1e293b" font-family="Inter, sans-serif" font-weight="800" font-size="18" letter-spacing="-0.5">EduCore</text>
        </svg>',
        'retailpro' => '<svg viewBox="0 0 135 30" height="26" class="trusted-logo" aria-label="RetailPro" role="img">
            <defs>
                <linearGradient id="' . $grad(4) . '" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#EC4899;stop-opacity:1"></stop>
                    <stop offset="100%" style="stop-color:#8B5CF6;stop-opacity:1"></stop>
                </linearGradient>
            </defs>
            <path d="M5 5h20l-4 15H9z" fill="none" stroke="url(#' . $grad(4) . ')" stroke-width="3.5" stroke-linejoin="round"></path>
            <circle cx="10" cy="25" r="2.5" fill="url(#' . $grad(4) . ')"></circle>
            <circle cx="20" cy="25" r="2.5" fill="url(#' . $grad(4) . ')"></circle>
            <text x="35" y="22" fill="#1e293b" font-family="Inter, sans-serif" font-weight="800" font-size="18" letter-spacing="-0.5">RetailPro</text>
        </svg>',
    ];

    return $logos[$key] ?? null;
}

function trustedClientLogoFallbackSvg(string $name, string $initials, int $uid = 0): string
{
    $gradId = 'grad-fallback-' . $uid;
    $label = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars($initials ?: strtoupper(substr($name, 0, 2)), ENT_QUOTES, 'UTF-8');

    return '<svg viewBox="0 0 120 30" height="26" class="trusted-logo trusted-logo-fallback" aria-label="' . $label . '" role="img">
        <defs>
            <linearGradient id="' . $gradId . '" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#0c3762;stop-opacity:1"></stop>
                <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1"></stop>
            </linearGradient>
        </defs>
        <rect x="2" y="2" width="26" height="26" rx="8" fill="none" stroke="url(#' . $gradId . ')" stroke-width="3"></rect>
        <text x="15" y="20" fill="url(#' . $gradId . ')" font-family="Inter, sans-serif" font-weight="800" font-size="11" text-anchor="middle">' . $text . '</text>
        <text x="38" y="21" fill="#1e293b" font-family="Inter, sans-serif" font-weight="800" font-size="16" letter-spacing="-0.5">' . $label . '</text>
    </svg>';
}
