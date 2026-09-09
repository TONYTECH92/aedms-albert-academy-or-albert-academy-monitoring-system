<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Classes & Subjects';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_class') {
        $name = trim($_POST['class_name'] ?? '');
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO classes (class_name) VALUES (?)');
            $stmt->execute([$name]);
            log_activity($pdo, current_user()['user_id'], "Added class: {$name}");
            flash_set('success', "Class '{$name}' added.");
        }
    }

    if ($action === 'add_subject') {
        $name = trim($_POST['subject_name'] ?? '');
        $classId = (int) ($_POST['class_id'] ?? 0);
        $teacherId = $_POST['teacher_id'] !== '' ? (int) $_POST['teacher_id'] : null;
        if ($name !== '' && $classId > 0) {
            $stmt = $pdo->prepare('INSERT INTO subjects (subject_name, class_id, teacher_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $classId, $teacherId]);
            log_activity($pdo, current_user()['user_id'], "Added subject: {$name}");
            flash_set('success', "Subject '{$name}' added.");
        }
    }

    header('Location: /admin/manage_classes.php');
    exit;
}

$classes = $pdo->query('SELECT * FROM classes ORDER BY class_name')->fetchAll();
$teachers = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetchAll();
$subjects = $pdo->query("
    SELECT s.subject_id, s.subject_name, c.class_name, u.full_name AS teacher_name
    FROM subjects s
    JOIN classes c ON c.class_id = s.class_id
    LEFT JOIN users u ON u.user_id = s.teacher_id
    ORDER BY c.class_name, s.subject_name
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1>Classes &amp; Subjects</h1>

<div class="grid grid-2">
    <div class="card">
        <h3>Add Class</h3>
        <form method="post" action="/admin/manage_classes.php">
            <input type="hidden" name="action" value="add_class">
            <label>Class Name</label>
            <input type="text" name="class_name" placeholder="e.g. Form 4B" required>
            <button type="submit" class="btn btn-sm" style="margin-top:14px;">Add Class</button>
        </form>
    </div>

    <div class="card">
        <h3>Add Subject</h3>
        <form method="post" action="/admin/manage_classes.php">
            <input type="hidden" name="action" value="add_subject">
            <label>Subject Name</label>
            <input type="text" name="subject_name" placeholder="e.g. Mathematics" required>
            <label>Class</label>
            <select name="class_id" required>
                <option value="">-- Select class --</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int)$c['class_id'] ?>"><?= h($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Teacher</label>
            <select name="teacher_id">
                <option value="">-- Unassigned --</option>
                <?php foreach ($teachers as $t): ?>
                <option value="<?= (int)$t['user_id'] ?>"><?= h($t['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm" style="margin-top:14px;">Add Subject</button>
        </form>
    </div>
</div>

<div class="card">
    <h3>Classes (<?= count($classes) ?>)</h3>
    <table>
        <tr><th>Class Name</th></tr>
        <?php foreach ($classes as $c): ?>
        <tr><td><?= h($c['class_name']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h3>Subjects (<?= count($subjects) ?>)</h3>
    <table>
        <tr><th>Subject</th><th>Class</th><th>Teacher</th></tr>
        <?php foreach ($subjects as $s): ?>
        <tr>
            <td><?= h($s['subject_name']) ?></td>
            <td><?= h($s['class_name']) ?></td>
            <td><?= h($s['teacher_name'] ?? '— unassigned —') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
