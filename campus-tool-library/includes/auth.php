<?php
/**
 * Authentication helpers.
 * Session-based auth: password_hash() / password_verify(), CSRF tokens,
 * and simple guard functions used at the top of protected pages.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

/** Is someone currently logged in? */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/** Force login before viewing a page; remembers where to return to. */
function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/** Fetch the full row for the logged-in user (or null). */
function current_user(): ?array {
    global $pdo;
    if (!is_logged_in()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

/** Generate / reuse a CSRF token for the current session. */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify a submitted CSRF token, die with 400 on mismatch. */
function verify_csrf(?string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

/** Small helper to escape output consistently. */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Flash message helpers (one-time notices shown after redirect). */
function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function flash_get(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
