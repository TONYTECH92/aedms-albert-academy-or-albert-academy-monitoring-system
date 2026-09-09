<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$pageTitle = 'Manage Users';

// ---- Handle new-user creation ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    $validRoles = ['admin', 'teacher', 'staff', 'pupil'];

    if ($fullName === '' || $email === '' || $password === '' || !in_array($role, $validRoles, true)) {
        flash_set('error', 'Please fill in all required fields correctly.');
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role,
                trim($_POST['phone'] ?? '') ?: null,
            ]);
            $newUserId = (int) $pdo->lastInsertId();

            if ($role === 'pupil') {
                $classId = (int) ($_POST['class_id'] ?? 0);
                $admissionNo = trim($_POST['admission_no'] ?? '');
                if ($classId <= 0 || $admissionNo === '') {
                    throw new Exception('Class and admission number are required for pupils.');
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO pupils (user_id, admission_no, class_id, guardian_name, guardian_contact, gender)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $newUserId, $admissionNo, $classId,
                    trim($_POST['guardian_name'] ?? '') ?: null,
                    trim($_POST['guardian_contact'] ?? '') ?: null,
                    $_POST['gender'] ?: null,
                ]);
            } elseif (in_array($role, ['teacher', 'staff'], true)) {
                $staffNo = trim($_POST['staff_no'] ?? '');
                if ($staffNo === '') {
                    throw new Exception('Staff number is required for teachers/staff.');
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO staff (user_id, staff_no, position, department, date_joined)
                     VALUES (?, ?, ?, ?, CURDATE())'
                );
                $stmt->execute([
                    $newUserId, $staffNo,
                    trim($_POST['position'] ?? '') ?: null,
                    trim($_POST['department'] ?? '') ?: null,
                ]);
            }

            log_activity($pdo, current_user()['user_id'], "Created new {$role} account: {$fullName}");
            $pdo->commit();
            flash_set('success', ucfirst($role) . " '{$fullName}' created successfully.");
        } catch (Exception $e) {
            $pdo->rollBack();
            flash_set('error', 'Could not create user: ' . $e->getMessage());
        }
    }
    header('Location: /admin/manage_users.php');
    exit;
}

$classes = $pdo->query('SELECT class_id, class_name FROM classes ORDER BY class_name')->fetchAll();

$users = $pdo->query("
    SELECT u.user_id, u.full_name, u.email, u.role, u.status, u.created_at,
           p.admission_no, c.class_name,
           s.staff_no, s.position
    FROM users u
    LEFT JOIN pupils p ON p.user_id = u.user_id
    LEFT JOIN classes c ON c.class_id = p.class_id
    LEFT JOIN staff s ON s.user_id = u.user_id
    ORDER BY u.role, u.full_name
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1>Manage Users</h1>
<p class="muted">Create and view accounts for the three system actors: teachers/staff, and pupils (admin accounts can also be created here).</p>

<div class="card">
    <h3>Add New User</h3>
    <form method="post" action="/admin/manage_users.php" id="createUserForm">
        <input type="hidden" name="action" value="create_user">
        <div class="grid grid-2">
            <div>
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
        </div>
        <div class="grid grid-2">
            <div>
                <label>Role</label>
                <select name="role" id="roleSelect" required onchange="toggleRoleFields()">
                    <option value="">-- Select role --</option>
                    <option value="pupil">Pupil</option>
                    <option value="teacher">Teacher</option>
                    <option value="staff">Non-teaching Staff</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div>
                <label>Temporary Password</label>
                <input type="text" name="password" required placeholder="e.g. Welcome@2026">
            </div>
        </div>
        <div class="grid grid-2">
            <div>
                <label>Phone (optional)</label>
                <input type="text" name="phone">
            </div>
        </div>

        <div id="pupilFields" style="display:none;">
            <div class="grid grid-2">
                <div>
                    <label>Admission Number</label>
                    <input type="text" name="admission_no">
                </div>
                <div>
                    <label>Class</label>
                    <select name="class_id">
                        <option value="">-- Select class --</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c['class_id'] ?>"><?= h($c['class_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-2">
                <div>
                    <label>Guardian Name</label>
                    <input type="text" name="guardian_name">
                </div>
                <div>
                    <label>Guardian Contact</label>
                    <input type="text" name="guardian_contact">
                </div>
            </div>
            <label>Gender</label>
            <select name="gender">
                <option value="">-- Select --</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
            </select>
        </div>

        <div id="staffFields" style="display:none;">
            <div class="grid grid-2">
                <div>
                    <label>Staff Number</label>
                    <input type="text" name="staff_no">
                </div>
                <div>
                    <label>Position</label>
                    <input type="text" name="position" placeholder="e.g. Mathematics Teacher">
                </div>
            </div>
            <label>Department</label>
            <input type="text" name="department">
        </div>

        <button type="submit" class="btn">Create User</button>
    </form>
</div>

<div class="card">
    <h3>All Users (<?= count($users) ?>)</h3>
    <table>
        <tr><th>Name</th><th>Role</th><th>Email</th><th>Details</th><th>Status</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= h($u['full_name']) ?></td>
            <td><span class="pill"><?= h(ucfirst($u['role'])) ?></span></td>
            <td><?= h($u['email']) ?></td>
            <td class="muted">
                <?php if ($u['role'] === 'pupil'): ?>
                    <?= h($u['admission_no']) ?> · <?= h($u['class_name']) ?>
                <?php elseif (in_array($u['role'], ['teacher','staff'], true)): ?>
                    <?= h($u['staff_no']) ?> · <?= h($u['position']) ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="badge badge-<?= $u['status'] === 'active' ? 'active' : 'withdrawn' ?>"><?= h(ucfirst($u['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function toggleRoleFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('pupilFields').style.display = role === 'pupil' ? 'block' : 'none';
    document.getElementById('staffFields').style.display = (role === 'teacher' || role === 'staff') ? 'block' : 'none';
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
