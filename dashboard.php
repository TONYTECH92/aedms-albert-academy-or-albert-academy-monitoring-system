<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher', 'staff']);

$pageTitle = 'Teacher Dashboard';
$userId = current_user()['user_id'];

$staffRow = $pdo->prepare('SELECT staff_id FROM staff WHERE user_id = ?');
$staffRow->execute([$userId]);
$staffId = $staffRow->fetchColumn();

$mySubjects = $pdo->prepare("
    SELECT s.subject_id, s.subject_name, c.class_id, c.class_name,
           (SELECT COUNT(*) FROM pupils p WHERE p.class_id = c.class_id) AS pupil_count
    FROM subjects s
    JOIN classes c ON c.class_id = s.class_id
    WHERE s.teacher_id = ?
    ORDER BY c.class_name, s.subject_name
");
$mySubjects->execute([$userId]);
$mySubjects = $mySubjects->fetchAll();

$myResponsibilities = [];
if ($staffId) {
    $stmt = $pdo->prepare("SELECT * FROM responsibilities WHERE staff_id = ? AND status = 'active' ORDER BY date_assigned DESC");
    $stmt->execute([$staffId]);
    $myResponsibilities = $stmt->fetchAll();
}

$classIds = array_column($mySubjects, 'class_id');
$attendanceTodayCount = 0;
if ($classIds) {
    $in = implode(',', array_fill(0, count($classIds), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT class_id) FROM student_attendance WHERE attendance_date = CURDATE() AND class_id IN ($in)");
    $stmt->execute($classIds);
    $attendanceTodayCount = (int) $stmt->fetchColumn();
}

require __DIR__ . '/../includes/header.php';
?>

<h1>Welcome, <?= h(current_user()['full_name']) ?></h1>
<p class="muted">Here's an overview of your classes and responsibilities.</p>

<div class="grid grid-3">
    <div class="stat">
        <div class="stat-value"><?= count($mySubjects) ?></div>
        <div class="stat-label">Subjects Assigned</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= count(array_unique($classIds)) ?></div>
        <div class="stat-label">Classes Taught</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= count($myResponsibilities) ?></div>
        <div class="stat-label">Active Responsibilities</div>
    </div>
</div>

<div class="card">
    <h3>My Subjects &amp; Classes</h3>
    <?php if (!$mySubjects): ?>
        <p class="muted">No subjects have been assigned to you yet. Contact the administrator.</p>
    <?php else: ?>
        <table>
            <tr><th>Subject</th><th>Class</th><th>Pupils</th><th></th></tr>
            <?php foreach ($mySubjects as $s): ?>
            <tr>
                <td><?= h($s['subject_name']) ?></td>
                <td><?= h($s['class_name']) ?></td>
                <td><?= (int) $s['pupil_count'] ?></td>
                <td><a class="btn btn-sm btn-outline" href="/teacher/mark_attendance.php?class_id=<?= (int)$s['class_id'] ?>">Mark Attendance</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>My Active Responsibilities</h3>
    <?php if (!$myResponsibilities): ?>
        <p class="muted">No responsibilities currently assigned to you.</p>
    <?php else: ?>
        <table>
            <tr><th>Title</th><th>Assigned</th><th>Due</th></tr>
            <?php foreach ($myResponsibilities as $r): ?>
            <tr>
                <td><?= h($r['title']) ?></td>
                <td><?= h($r['date_assigned']) ?></td>
                <td><?= h($r['due_date'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
