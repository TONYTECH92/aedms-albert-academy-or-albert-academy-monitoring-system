<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('pupil');

$pageTitle = 'My Attendance';
$userId = current_user()['user_id'];

$pupil = $pdo->prepare('SELECT pupil_id FROM pupils WHERE user_id = ?');
$pupil->execute([$userId]);
$pupilId = $pupil->fetchColumn();

$history = $pdo->prepare("
    SELECT attendance_date, status, remarks
    FROM student_attendance
    WHERE pupil_id = ?
    ORDER BY attendance_date DESC
    LIMIT 60
");
$history->execute([$pupilId]);
$history = $history->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1>My Attendance</h1>
<div class="card">
    <?php if (!$history): ?>
        <p class="muted">No attendance records yet.</p>
    <?php else: ?>
        <table>
            <tr><th>Date</th><th>Status</th><th>Remarks</th></tr>
            <?php foreach ($history as $h_): ?>
            <tr>
                <td><?= h($h_['attendance_date']) ?></td>
                <td><span class="badge badge-<?= h($h_['status']) ?>"><?= h(ucfirst($h_['status'])) ?></span></td>
                <td class="muted"><?= h($h_['remarks'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
