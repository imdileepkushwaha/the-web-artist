<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/includes/helpers.php';

function loadSiteServices(PDO $conn): array
{
    try {
        $services = getServices($conn, true);
        return $services ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function loadSiteTestimonials(PDO $conn): array
{
    try {
        return getTestimonials($conn, true) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function loadSiteFaq(PDO $conn): array
{
    try {
        return getFaqItems($conn, true) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function loadSitePortfolio(PDO $conn): array
{
    try {
        return getPortfolioProjects($conn, true) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function loadSiteTrustedClients(PDO $conn): array
{
    try {
        return getTrustedClients($conn, true) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function initSiteContent(): array
{
    $conn = getDbConnection();

    return [
        'services' => loadSiteServices($conn),
        'testimonials' => loadSiteTestimonials($conn),
        'faq' => loadSiteFaq($conn),
        'portfolio' => loadSitePortfolio($conn),
        'trusted_clients' => loadSiteTrustedClients($conn),
    ];
}
