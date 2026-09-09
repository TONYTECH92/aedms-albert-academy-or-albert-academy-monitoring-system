<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';

$totalPupils   = $pdo->query("SELECT COUNT(*) FROM pupils")->fetchColumn();
$totalStaff    = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$totalClasses  = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

$today = date('Y-m-d');

$pupilsPresentToday = $pdo->prepare(
    "SELECT COUNT(*) FROM student_attendance WHERE attendance_date = ? AND status = 'present'"
);
$pupilsPresentToday->execute([$today]);
$pupilsPresentToday = (int) $pupilsPresentToday->fetchColumn();

$pupilsMarkedToday = $pdo->prepare(
    "SELECT COUNT(*) FROM student_attendance WHERE attendance_date = ?"
);
$pupilsMarkedToday->execute([$today]);
$pupilsMarkedToday = (int) $pupilsMarkedToday->fetchColumn();

$studentAttendanceRate = $pupilsMarkedToday > 0
    ? round(($pupilsPresentToday / $pupilsMarkedToday) * 100, 1)
    : null;

$staffPresentToday = $pdo->prepare(
    "SELECT COUNT(*) FROM staff_attendance WHERE attendance_date = ? AND status = 'present'"
);
$staffPresentToday->execute([$today]);
$staffPresentToday = (int) $staffPresentToday->fetchColumn();

$staffMarkedToday = $pdo->prepare(
    "SELECT COUNT(*) FROM staff_attendance WHERE attendance_date = ?"
);
$staffMarkedToday->execute([$today]);
$staffMarkedToday = (int) $staffMarkedToday->fetchColumn();

$avgPerformance = $pdo->query(
    "SELECT ROUND(AVG(total_score),1) FROM academic_records WHERE academic_year = " .
    $pdo->quote(current_academic_year())
)->fetchColumn();

// Attendance trend, last 7 days
$trend = $pdo->query("
    SELECT attendance_date,
           SUM(status='present') AS present_count,
           COUNT(*) AS total_count
    FROM student_attendance
    WHERE attendance_date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY attendance_date
    ORDER BY attendance_date
")->fetchAll();

// Classes needing attendance marked today
$classesPendingToday = $pdo->prepare("
    SELECT c.class_id, c.class_name
    FROM classes c
    WHERE c.class_id NOT IN (
        SELECT DISTINCT class_id FROM student_attendance WHERE attendance_date = ?
    )
");
$classesPendingToday->execute([$today]);
$classesPendingToday = $classesPendingToday->fetchAll();

// Recent activity
$recentActivity = $pdo->query("
    SELECT al.action, al.created_at, u.full_name, u.role
    FROM activity_log al
    LEFT JOIN users u ON u.user_id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 8
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h1 style="margin:0;">Admin Dashboard</h1>
    <span class="pill"><?= h(current_academic_year()) ?></span>
</div>
<p class="muted">Overview of Albert Academy's monitoring data for today, <?= date('l, j F Y') ?>.</p>

<div class="grid grid-3">
    <div class="stat">
        <div class="stat-value"><?= (int) $totalPupils ?></div>
        <div class="stat-label">Total Pupils</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= (int) $totalStaff ?></div>
        <div class="stat-label">Teachers &amp; Staff</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= (int) $totalClasses ?></div>
        <div class="stat-label">Classes</div>
    </div>
</div>

<div class="grid grid-3" style="margin-top:4px;">
    <div class="stat">
        <div class="stat-value"><?= $studentAttendanceRate !== null ? $studentAttendanceRate . '%' : '—' ?></div>
        <div class="stat-label">Pupil Attendance Today (<?= $pupilsMarkedToday ?> marked)</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= $staffMarkedToday > 0 ? round(($staffPresentToday / $staffMarkedToday) * 100, 1) . '%' : '—' ?></div>
        <div class="stat-label">Staff Attendance Today (<?= $staffMarkedToday ?> marked)</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= $avgPerformance !== null ? $avgPerformance : '—' ?></div>
        <div class="stat-label">Avg. Academic Score (this year)</div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:6px;">
    <div class="card">
        <h3>Pupil Attendance — Last 7 Days</h3>
        <?php if (!$trend): ?>
            <p class="muted">No attendance data recorded yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Date</th><th>Present</th><th>Marked</th><th>Rate</th></tr>
                <?php foreach ($trend as $row): $rate = $row['total_count'] > 0 ? round(($row['present_count'] / $row['total_count']) * 100, 1) : 0; ?>
                <tr>
                    <td><?= h($row['attendance_date']) ?></td>
                    <td><?= (int) $row['present_count'] ?></td>
                    <td><?= (int) $row['total_count'] ?></td>
                    <td><?= $rate ?>%</td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Classes Not Yet Marked Today</h3>
        <?php if (!$classesPendingToday): ?>
            <p class="muted">All classes have attendance recorded for today.</p>
        <?php else: ?>
            <table>
                <tr><th>Class</th></tr>
                <?php foreach ($classesPendingToday as $c): ?>
                <tr><td><?= h($c['class_name']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Recent Activity</h3>
    <?php if (!$recentActivity): ?>
        <p class="muted">No activity logged yet.</p>
    <?php else: ?>
        <table>
            <tr><th>When</th><th>User</th><th>Role</th><th>Action</th></tr>
            <?php foreach ($recentActivity as $a): ?>
            <tr>
                <td><?= h($a['created_at']) ?></td>
                <td><?= h($a['full_name'] ?? 'System') ?></td>
                <td><?= h($a['role'] ?? '—') ?></td>
                <td><?= h($a['action']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
