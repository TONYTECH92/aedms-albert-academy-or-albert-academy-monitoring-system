<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher', 'staff']);

$pageTitle = 'My Responsibilities';
$userId = current_user()['user_id'];

$staffRow = $pdo->prepare('SELECT staff_id FROM staff WHERE user_id = ?');
$staffRow->execute([$userId]);
$staffId = $staffRow->fetchColumn();

$responsibilities = [];
if ($staffId) {
    $stmt = $pdo->prepare('SELECT * FROM responsibilities WHERE staff_id = ? ORDER BY date_assigned DESC');
    $stmt->execute([$staffId]);
    $responsibilities = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>

<h1>My Responsibilities</h1>
<p class="muted">Duties assigned to you by the school administrator.</p>

<div class="card">
    <?php if (!$responsibilities): ?>
        <p class="muted">No responsibilities have been assigned to you yet.</p>
    <?php else: ?>
        <table>
            <tr><th>Title</th><th>Description</th><th>Assigned</th><th>Due</th><th>Status</th></tr>
            <?php foreach ($responsibilities as $r): ?>
            <tr>
                <td><?= h($r['title']) ?></td>
                <td class="muted"><?= h($r['description'] ?? '—') ?></td>
                <td><?= h($r['date_assigned']) ?></td>
                <td><?= h($r['due_date'] ?? '—') ?></td>
                <td><span class="badge badge-<?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
