<?php
/**
 * Shared header. Expects $pageTitle to be set by the including page.
 */
$user = current_user();
$role = $user['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'AEDMS') ?> · Albert Academy AEDMS</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <span class="brand-mark">AA</span>
        <div>
            <strong>Albert Academy</strong>
            <div class="brand-sub">Digital Education Monitoring System</div>
        </div>
    </div>
    <?php if ($user): ?>
    <nav class="topnav">
        <?php if ($role === 'admin'): ?>
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/manage_users.php">Users</a>
            <a href="/admin/manage_classes.php">Classes &amp; Subjects</a>
            <a href="/admin/staff_attendance.php">Staff Attendance</a>
            <a href="/admin/assign_responsibility.php">Responsibilities</a>
        <?php elseif ($role === 'teacher' || $role === 'staff'): ?>
            <a href="/teacher/dashboard.php">Dashboard</a>
            <a href="/teacher/mark_attendance.php">Mark Attendance</a>
            <a href="/teacher/enter_scores.php">Enter Scores</a>
            <a href="/teacher/my_responsibilities.php">My Responsibilities</a>
        <?php elseif ($role === 'pupil'): ?>
            <a href="/pupil/dashboard.php">Dashboard</a>
            <a href="/pupil/attendance.php">My Attendance</a>
            <a href="/pupil/performance.php">My Results</a>
        <?php endif; ?>
    </nav>
    <div class="userbox">
        <span><?= h($user['full_name']) ?> <em>(<?= h(ucfirst($role)) ?>)</em></span>
        <a href="/logout.php" class="btn-link">Log out</a>
    </div>
    <?php endif; ?>
</header>
<main class="container">
<?php $flash = flash_get(); if ($flash): ?>
    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
