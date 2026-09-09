<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('pupil');

$pageTitle = 'My Results';
$userId = current_user()['user_id'];

$pupil = $pdo->prepare('SELECT pupil_id FROM pupils WHERE user_id = ?');
$pupil->execute([$userId]);
$pupilId = $pupil->fetchColumn();

$year = $_GET['year'] ?? current_academic_year();

$results = $pdo->prepare("
    SELECT sub.subject_name, ar.term, ar.ca_score, ar.exam_score, ar.total_score, ar.grade, ar.remarks
    FROM academic_records ar
    JOIN subjects sub ON sub.subject_id = ar.subject_id
    WHERE ar.pupil_id = ? AND ar.academic_year = ?
    ORDER BY sub.subject_name, ar.term
");
$results->execute([$pupilId, $year]);
$results = $results->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1>My Results</h1>
<p class="muted">Academic Year: <?= h($year) ?></p>

<div class="card">
    <?php if (!$results): ?>
        <p class="muted">No results recorded yet for this academic year.</p>
    <?php else: ?>
        <table>
            <tr><th>Subject</th><th>Term</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Remarks</th></tr>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= h($r['subject_name']) ?></td>
                <td><?= h($r['term']) ?></td>
                <td><?= $r['ca_score'] !== null ? h($r['ca_score']) : '—' ?></td>
                <td><?= $r['exam_score'] !== null ? h($r['exam_score']) : '—' ?></td>
                <td><strong><?= $r['total_score'] !== null ? h($r['total_score']) : '—' ?></strong></td>
                <td><?= $r['grade'] ? '<span class="badge badge-'.h($r['grade']).'">'.h($r['grade']).'</span>' : '—' ?></td>
                <td class="muted"><?= h($r['remarks'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
