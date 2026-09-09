<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Staff Attendance';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_attendance') {
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $statuses = $_POST['status'] ?? [];
    $timeIns  = $_POST['time_in'] ?? [];

    $stmt = $pdo->prepare("
        INSERT INTO staff_attendance (staff_id, attendance_date, status, time_in, marked_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), time_in = VALUES(time_in), marked_by = VALUES(marked_by)
    ");
    $adminId = current_user()['user_id'];
    $count = 0;
    foreach ($statuses as $staffId => $status) {
        $timeIn = $timeIns[$staffId] !== '' ? $timeIns[$staffId] : null;
        $stmt->execute([(int) $staffId, $date, $status, $timeIn, $adminId]);
        $count++;
    }
    log_activity($pdo, $adminId, "Marked staff attendance for {$date} ({$count} staff)");
    flash_set('success', "Staff attendance recorded for {$date}.");
    header('Location: /admin/staff_attendance.php?date=' . urlencode($date));
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');

$staffList = $pdo->prepare("
    SELECT s.staff_id, u.full_name, s.position, s.department,
           sa.status AS today_status, sa.time_in AS today_time_in
    FROM staff s
    JOIN users u ON u.user_id = s.user_id
    LEFT JOIN staff_attendance sa ON sa.staff_id = s.staff_id AND sa.attendance_date = ?
    ORDER BY u.full_name
");
$staffList->execute([$date]);
$staffList = $staffList->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1>Staff Attendance</h1>

<form method="get" action="/admin/staff_attendance.php" style="max-width:220px;">
    <label>Date</label>
    <input type="date" name="date" value="<?= h($date) ?>" onchange="this.form.submit()">
</form>

<div class="card">
    <h3>Mark Attendance — <?= h($date) ?></h3>
    <?php if (!$staffList): ?>
        <p class="muted">No staff records found. Add teachers/staff under Manage Users first.</p>
    <?php else: ?>
    <form method="post" action="/admin/staff_attendance.php">
        <input type="hidden" name="action" value="mark_attendance">
        <input type="hidden" name="attendance_date" value="<?= h($date) ?>">
        <table>
            <tr><th>Name</th><th>Position</th><th>Status</th><th>Time In</th></tr>
            <?php foreach ($staffList as $s): ?>
            <tr>
                <td><?= h($s['full_name']) ?></td>
                <td class="muted"><?= h($s['position']) ?></td>
                <td>
                    <select name="status[<?= (int)$s['staff_id'] ?>]">
                        <?php foreach (['present','late','absent','on_leave'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($s['today_status'] === $opt) ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$opt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="time" name="time_in[<?= (int)$s['staff_id'] ?>]" value="<?= h($s['today_time_in']) ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit" class="btn">Save Attendance</button>
    </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
