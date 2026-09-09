<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Assign Responsibilities';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dueDate = $_POST['due_date'] ?: null;

        if ($staffId > 0 && $title !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO responsibilities (staff_id, title, description, date_assigned, due_date, assigned_by)
                VALUES (?, ?, ?, CURDATE(), ?, ?)
            ");
            $stmt->execute([$staffId, $title, $description ?: null, $dueDate, current_user()['user_id']]);
            log_activity($pdo, current_user()['user_id'], "Assigned responsibility '{$title}'");
            flash_set('success', "Responsibility '{$title}' assigned.");
        }
    }

    if ($action === 'update_status') {
        $respId = (int) ($_POST['responsibility_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $stmt = $pdo->prepare('UPDATE responsibilities SET status = ? WHERE responsibility_id = ?');
        $stmt->execute([$status, $respId]);
        flash_set('success', 'Responsibility status updated.');
    }

    header('Location: /admin/assign_responsibility.php');
    exit;
}

$staffOptions = $pdo->query("
    SELECT s.staff_id, u.full_name, s.position
    FROM staff s JOIN users u ON u.user_id = s.user_id
    ORDER BY u.full_name
")->fetchAll();

$responsibilities = $pdo->query("
    SELECT r.*, u.full_name AS staff_name
    FROM responsibilities r
    JOIN staff s ON s.staff_id = r.staff_id
    JOIN users u ON u.user_id = s.user_id
    ORDER BY r.date_assigned DESC
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1>Assign Responsibilities</h1>
<p class="muted">Assign duties (e.g. Head of Department, Exam Coordinator, ICT Lead) to teaching and non-teaching staff.</p>

<div class="card">
    <h3>New Assignment</h3>
    <form method="post" action="/admin/assign_responsibility.php">
        <input type="hidden" name="action" value="assign">
        <div class="grid grid-2">
            <div>
                <label>Staff Member</label>
                <select name="staff_id" required>
                    <option value="">-- Select staff --</option>
                    <?php foreach ($staffOptions as $s): ?>
                    <option value="<?= (int)$s['staff_id'] ?>"><?= h($s['full_name']) ?> <?= $s['position'] ? '(' . h($s['position']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Title</label>
                <input type="text" name="title" placeholder="e.g. Head of Mathematics Department" required>
            </div>
        </div>
        <label>Description (optional)</label>
        <textarea name="description" rows="3"></textarea>
        <label>Due Date (optional)</label>
        <input type="date" name="due_date">
        <button type="submit" class="btn">Assign Responsibility</button>
    </form>
</div>

<div class="card">
    <h3>All Responsibilities (<?= count($responsibilities) ?>)</h3>
    <table>
        <tr><th>Staff</th><th>Title</th><th>Assigned</th><th>Due</th><th>Status</th><th>Update</th></tr>
        <?php foreach ($responsibilities as $r): ?>
        <tr>
            <td><?= h($r['staff_name']) ?></td>
            <td><?= h($r['title']) ?></td>
            <td><?= h($r['date_assigned']) ?></td>
            <td><?= h($r['due_date'] ?? '—') ?></td>
            <td><span class="badge badge-<?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span></td>
            <td>
                <form method="post" action="/admin/assign_responsibility.php" style="display:flex;gap:6px;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="responsibility_id" value="<?= (int)$r['responsibility_id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <?php foreach (['active','completed','withdrawn'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $r['status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
