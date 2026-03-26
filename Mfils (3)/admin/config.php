<?php
// admin/config.php
require_once __DIR__ . '/../includes/functions.php';

function adminStartSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('mlm_admin_session');
        session_start();
    }
}

function isAdminLoggedIn(): bool {
    adminStartSession();
    return !empty($_SESSION['admin_logged_in']);
}

function requireAdmin(): void {
    adminStartSession();
    if (!isAdminLoggedIn()) {
       header('Location: ' . rtrim(APP_URL,'/') . '/admin/login');
        exit;
    }
}

function adminLogout(): void {
    adminStartSession();
    session_destroy();
    header('Location: ' . rtrim(APP_URL,'/') . '/admin/login');
    exit;
}

function setAdminFlash(string $type, string $msg): void {
    adminStartSession();
    $_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
}

function getAdminFlash(): ?array {
    adminStartSession();
    $f = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $f;
}

function inr(float $v): string {
    return '₹' . number_format($v, 2);
}

function pct(float $v): string {
    return number_format($v, 2) . '%';
}

// FIXED: Renamed from timeAgo to adminTimeAgo to avoid conflict
function adminTimeAgo(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}