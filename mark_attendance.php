<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher', 'staff']);

$pageTitle = 'Mark Attendance';
$userId = current_user()['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark') {
    $classId = (int) ($_POST['class_id'] ?? 0);
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $statuses = $_POST['status'] ?? [];

    $stmt = $pdo->prepare("
        INSERT INTO student_attendance (pupil_id, class_id, attendance_date, status, marked_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)
    ");
    $count = 0;
    foreach ($statuses as $pupilId => $status) {
        $stmt->execute([(int) $pupilId, $classId, $date, $status, $userId]);
        $count++;
    }
    log_activity($pdo, $userId, "Marked attendance for class #{$classId} on {$date} ({$count} pupils)");
    flash_set('success', "Attendance saved for {$date} ({$count} pupils).");
    header("Location: /teacher/mark_attendance.php?class_id={$classId}&date=" . urlencode($date));
    exit;
}

// Classes this teacher teaches (or all classes, if non-teaching staff with admin-granted access)
$myClasses = $pdo->prepare("
    SELECT DISTINCT c.class_id, c.class_name
    FROM subjects s JOIN classes c ON c.class_id = s.class_id
    WHERE s.teacher_id = ?
    ORDER BY c.class_name
");
$myClasses->execute([$userId]);
$myClasses = $myClasses->fetchAll();

$classId = (int) ($_GET['class_id'] ?? ($myClasses[0]['class_id'] ?? 0));
$date = $_GET['date'] ?? date('Y-m-d');

$pupils = [];
if ($classId) {
    $stmt = $pdo->prepare("
        SELECT p.pupil_id, u.full_name, p.admission_no, sa.status AS today_status
        FROM pupils p
        JOIN users u ON u.user_id = p.user_id
        LEFT JOIN student_attendance sa ON sa.pupil_id = p.pupil_id AND sa.attendance_date = ?
        WHERE p.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$date, $classId]);
    $pupils = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>

<h1>Mark Student Attendance</h1>

<?php if (!$myClasses): ?>
    <div class="card"><p class="muted">You have no classes assigned yet. Contact the administrator.</p></div>
<?php else: ?>

<form method="get" action="/teacher/mark_attendance.php" class="grid grid-2" style="max-width:500px;">
    <div>
        <label>Class</label>
        <select name="class_id" onchange="this.form.submit()">
            <?php foreach ($myClasses as $c): ?>
            <option value="<?= (int)$c['class_id'] ?>" <?= $classId === (int)$c['class_id'] ? 'selected' : '' ?>><?= h($c['class_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Date</label>
        <input type="date" name="date" value="<?= h($date) ?>" onchange="this.form.submit()">
    </div>
</form>

<div class="card">
    <h3>Attendance — <?= h($date) ?></h3>
    <?php if (!$pupils): ?>
        <p class="muted">No pupils found in this class.</p>
    <?php else: ?>
    <form method="post" action="/teacher/mark_attendance.php">
        <input type="hidden" name="action" value="mark">
        <input type="hidden" name="class_id" value="<?= $classId ?>">
        <input type="hidden" name="attendance_date" value="<?= h($date) ?>">
        <table>
            <tr><th>Admission No.</th><th>Pupil Name</th><th>Status</th></tr>
            <?php foreach ($pupils as $p): ?>
            <tr>
                <td><?= h($p['admission_no']) ?></td>
                <td><?= h($p['full_name']) ?></td>
                <td>
                    <select name="status[<?= (int)$p['pupil_id'] ?>]">
                        <?php foreach (['present','late','absent'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($p['today_status'] === $opt || (!$p['today_status'] && $opt==='present')) ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit" class="btn">Save Attendance</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
