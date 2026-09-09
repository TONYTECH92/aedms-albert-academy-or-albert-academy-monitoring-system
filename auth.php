<?php
/**
 * Authentication & Role-Based Access Control
 */
session_start();
require_once __DIR__ . '/../config/db.php';

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'user_id'   => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ];
}

/** Redirect to login if not authenticated */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

/** Restrict a page to one or more roles, e.g. require_role('admin') or require_role(['admin','teacher']) */
function require_role($roles): void {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied: you do not have permission to view this page.');
    }
}

/** Log an action to the activity_log table */
function log_activity(PDO $pdo, ?int $userId, string $action): void {
    $stmt = $pdo->prepare('INSERT INTO activity_log (user_id, action) VALUES (?, ?)');
    $stmt->execute([$userId, $action]);
}

/** Attempt to log a user in; returns true on success */
function attempt_login(PDO $pdo, string $email, string $password): bool {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        log_activity($pdo, $user['user_id'], 'Logged in');
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}

/** Where to send a user after login, based on role */
function dashboard_url_for(string $role): string {
    return match ($role) {
        'admin'   => '/admin/dashboard.php',
        'teacher' => '/teacher/dashboard.php',
        'staff'   => '/teacher/dashboard.php', // staff share the teacher/staff portal
        'pupil'   => '/pupil/dashboard.php',
        default   => '/login.php',
    };
}
