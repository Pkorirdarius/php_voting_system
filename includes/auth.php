<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_voter(): void {
    if (empty($_SESSION['voter_id'])) {
        header('Location: /voter/login.php');
        exit;
    }
}

function require_candidate(): void {
    if (empty($_SESSION['candidate_id'])) {
        header('Location: /candidate/login.php');
        exit;
    }
}

function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function is_logged_in_voter(): bool   { return !empty($_SESSION['voter_id']); }
function is_logged_in_candidate(): bool { return !empty($_SESSION['candidate_id']); }
function is_logged_in_admin(): bool   { return !empty($_SESSION['admin_id']); }

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
