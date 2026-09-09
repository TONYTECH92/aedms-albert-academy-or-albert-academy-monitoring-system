<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher', 'staff']);

$pageTitle = 'Enter Scores';
$userId = current_user()['user_id'];
$year = current_academic_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_scores') {
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $term = $_POST['term'] ?? 'Term 1';
    $ca = $_POST['ca'] ?? [];
    $exam = $_POST['exam'] ?? [];

    $stmt = $pdo->prepare("
        INSERT INTO academic_records (pupil_id, subject_id, academic_year, term, ca_score, exam_score, grade, recorded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE ca_score = VALUES(ca_score), exam_score = VALUES(exam_score),
                                 grade = VALUES(grade), recorded_by = VALUES(recorded_by)
    ");
    $count = 0;
    foreach ($ca as $pupilId => $caVal) {
        $caVal = $caVal === '' ? null : (float) $caVal;
        $examVal = isset($exam[$pupilId]) && $exam[$pupilId] !== '' ? (float) $exam[$pupilId] : null;
        $total = (float) ($caVal ?? 0) + (float) ($examVal ?? 0);
        $grade = ($caVal !== null || $examVal !== null) ? score_to_grade($total) : null;
        $stmt->execute([(int) $pupilId, $subjectId, $year, $term, $caVal, $examVal, $grade, $userId]);
        $count++;
    }
    log_activity($pdo, $userId, "Entered scores for subject #{$subjectId}, {$term} ({$count} pupils)");
    flash_set('success', "Scores saved for {$term} ({$count} pupils).");
    header("Location: /teacher/enter_scores.php?subject_id={$subjectId}&term=" . urlencode($term));
    exit;
}

$mySubjects = $pdo->prepare("
    SELECT s.subject_id, s.subject_name, c.class_id, c.class_name
    FROM subjects s JOIN classes c ON c.class_id = s.class_id
    WHERE s.teacher_id = ?
    ORDER BY c.class_name, s.subject_name
");
$mySubjects->execute([$userId]);
$mySubjects = $mySubjects->fetchAll();

$subjectId = (int) ($_GET['subject_id'] ?? ($mySubjects[0]['subject_id'] ?? 0));
$term = $_GET['term'] ?? 'Term 1';

$records = [];
if ($subjectId) {
    $subjectClass = $pdo->prepare('SELECT class_id FROM subjects WHERE subject_id = ?');
    $subjectClass->execute([$subjectId]);
    $classId = $subjectClass->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.pupil_id, u.full_name, p.admission_no,
               ar.ca_score, ar.exam_score, ar.total_score, ar.grade
        FROM pupils p
        JOIN users u ON u.user_id = p.user_id
        LEFT JOIN academic_records ar ON ar.pupil_id = p.pupil_id AND ar.subject_id = ?
             AND ar.academic_year = ? AND ar.term = ?
        WHERE p.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$subjectId, $year, $term, $classId]);
    $records = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>

<h1>Enter Academic Scores</h1>
<p class="muted">Academic Year: <?= h($year) ?></p>

<?php if (!$mySubjects): ?>
    <div class="card"><p class="muted">No subjects assigned to you yet.</p></div>
<?php else: ?>

<form method="get" action="/teacher/enter_scores.php" class="grid grid-2" style="max-width:500px;">
    <div>
        <label>Subject</label>
        <select name="subject_id" onchange="this.form.submit()">
            <?php foreach ($mySubjects as $s): ?>
            <option value="<?= (int)$s['subject_id'] ?>" <?= $subjectId === (int)$s['subject_id'] ? 'selected' : '' ?>>
                <?= h($s['subject_name']) ?> — <?= h($s['class_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Term</label>
        <select name="term" onchange="this.form.submit()">
            <?php foreach (['Term 1','Term 2','Term 3'] as $t): ?>
            <option value="<?= $t ?>" <?= $term === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card">
    <h3>Scores — <?= h($term) ?> (CA = Continuous Assessment /40, Exam /60)</h3>
    <?php if (!$records): ?>
        <p class="muted">No pupils found for this subject's class.</p>
    <?php else: ?>
    <form method="post" action="/teacher/enter_scores.php">
        <input type="hidden" name="action" value="save_scores">
        <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
        <input type="hidden" name="term" value="<?= h($term) ?>">
        <table>
            <tr><th>Admission No.</th><th>Pupil</th><th>CA (/40)</th><th>Exam (/60)</th><th>Total</th><th>Grade</th></tr>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= h($r['admission_no']) ?></td>
                <td><?= h($r['full_name']) ?></td>
                <td><input type="number" step="0.01" min="0" max="40" name="ca[<?= (int)$r['pupil_id'] ?>]" value="<?= h($r['ca_score']) ?>"></td>
                <td><input type="number" step="0.01" min="0" max="60" name="exam[<?= (int)$r['pupil_id'] ?>]" value="<?= h($r['exam_score']) ?>"></td>
                <td><?= $r['total_score'] !== null ? h($r['total_score']) : '—' ?></td>
                <td><?= $r['grade'] ? '<span class="badge badge-'.h($r['grade']).'">'.h($r['grade']).'</span>' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit" class="btn">Save Scores</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
