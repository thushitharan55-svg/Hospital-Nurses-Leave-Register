<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$nurse = null;
$email = '';
if ($id) {
    $stmt = db()->prepare(
        'SELECT n.*, u.email, u.status AS user_status, u.id AS uid
         FROM nurses n JOIN users u ON u.id = n.user_id WHERE n.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $nurse = $stmt->fetch();
    if (!$nurse) {
        flash_set('error', 'Nurse not found.');
        redirect('admin/nurses.php');
    }
    $email = $nurse['email'];
}

$errors = [];
$fields = [
    'full_name' => $nurse['full_name'] ?? '',
    'email' => $email,
    'nic' => $nurse['nic'] ?? '',
    'phone' => $nurse['phone'] ?? '',
    'gender' => $nurse['gender'] ?? 'female',
    'ward' => $nurse['ward'] ?? '',
    'department' => $nurse['department'] ?? '',
    'designation' => $nurse['designation'] ?? 'Staff Nurse',
    'address' => $nurse['address'] ?? '',
    'date_of_joining' => $nurse['date_of_joining'] ?? '',
    'user_status' => $nurse['user_status'] ?? 'active',
];

if (is_post()) {
    verify_csrf();
    foreach ($fields as $key => $_) {
        if (isset($_POST[$key])) {
            $fields[$key] = post_str($key);
        }
    }
    $password = (string) ($_POST['password'] ?? '');

    if (strlen($fields['full_name']) < 3 || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid name and email are required.';
    }
    if (!$id && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters for a new account.';
    }
    if ($id && $password !== '' && strlen($password) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    $emailCheck = db()->prepare('SELECT id FROM users WHERE email = :email AND id <> :uid LIMIT 1');
    $emailCheck->execute([
        'email' => strtolower($fields['email']),
        'uid'   => $id ? (int) $nurse['uid'] : 0,
    ]);
    if ($emailCheck->fetch()) {
        $errors[] = 'Email is already in use.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if (!$id) {
                $u = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, role, status) VALUES (:email, :hash, \'nurse\', :status)'
                );
                $u->execute([
                    'email'  => strtolower($fields['email']),
                    'hash'   => password_hash($password, PASSWORD_DEFAULT),
                    'status' => $fields['user_status'] === 'inactive' ? 'inactive' : 'active',
                ]);
                $userId = (int) $pdo->lastInsertId();
                $n = $pdo->prepare(
                    'INSERT INTO nurses (user_id, employee_no, full_name, nic, gender, phone, ward, department, designation, address, date_of_joining)
                     VALUES (:uid, :emp, :name, :nic, :gender, :phone, :ward, :dept, :desig, :addr, :doj)'
                );
                $n->execute([
                    'uid' => $userId,
                    'emp' => next_employee_no(),
                    'name' => $fields['full_name'],
                    'nic' => $fields['nic'],
                    'gender' => $fields['gender'],
                    'phone' => $fields['phone'],
                    'ward' => $fields['ward'],
                    'dept' => $fields['department'],
                    'desig' => $fields['designation'] ?: 'Staff Nurse',
                    'addr' => $fields['address'] ?: null,
                    'doj' => $fields['date_of_joining'] ?: date('Y-m-d'),
                ]);
                ensure_balance((int) $pdo->lastInsertId());
            } else {
                $u = $pdo->prepare('UPDATE users SET email = :email, status = :status WHERE id = :id');
                $u->execute([
                    'email' => strtolower($fields['email']),
                    'status' => $fields['user_status'] === 'inactive' ? 'inactive' : 'active',
                    'id' => (int) $nurse['uid'],
                ]);
                if ($password !== '') {
                    $p = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                    $p->execute(['h' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int) $nurse['uid']]);
                }
                $n = $pdo->prepare(
                    'UPDATE nurses SET full_name=:name, nic=:nic, gender=:gender, phone=:phone, ward=:ward,
                     department=:dept, designation=:desig, address=:addr, date_of_joining=:doj WHERE id=:id'
                );
                $n->execute([
                    'name' => $fields['full_name'],
                    'nic' => $fields['nic'],
                    'gender' => $fields['gender'],
                    'phone' => $fields['phone'],
                    'ward' => $fields['ward'],
                    'dept' => $fields['department'],
                    'desig' => $fields['designation'] ?: 'Staff Nurse',
                    'addr' => $fields['address'] ?: null,
                    'doj' => $fields['date_of_joining'],
                    'id' => $id,
                ]);
            }
            $pdo->commit();
            flash_set('success', $id ? 'Nurse updated.' : 'Nurse created.');
            redirect('admin/nurses.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Could not save nurse.';
        }
    }
}

render_header($id ? 'Edit nurse' : 'Add nurse');
?>
<div class="panel">
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="form grid-2">
        <?= csrf_field() ?>
        <label>Full name<input name="full_name" value="<?= e($fields['full_name']) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= e($fields['email']) ?>" required></label>
        <label>NIC<input name="nic" value="<?= e($fields['nic']) ?>" required></label>
        <label>Phone<input name="phone" value="<?= e($fields['phone']) ?>" required></label>
        <label>Gender
            <select name="gender">
                <?php foreach (['female'=>'Female','male'=>'Male','other'=>'Other'] as $val=>$label): ?>
                    <option value="<?= $val ?>" <?= $fields['gender']===$val?'selected':'' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date of joining<input type="date" name="date_of_joining" value="<?= e($fields['date_of_joining']) ?>" required></label>
        <label>Ward<input name="ward" value="<?= e($fields['ward']) ?>" required></label>
        <label>Department<input name="department" value="<?= e($fields['department']) ?>" required></label>
        <label>Designation<input name="designation" value="<?= e($fields['designation']) ?>"></label>
        <label>Address<input name="address" value="<?= e($fields['address']) ?>"></label>
        <label>Account status
            <select name="user_status">
                <option value="active" <?= $fields['user_status']==='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $fields['user_status']==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </label>
        <label><?= $id ? 'Reset password (optional)' : 'Password' ?>
            <input type="password" name="password" <?= $id ? '' : 'required minlength="8"' ?> autocomplete="new-password">
        </label>
        <div class="form-span"><button class="btn" type="submit">Save</button>
            <a class="btn btn-ghost" href="<?= e(url('admin/nurses.php')) ?>">Cancel</a></div>
    </form>
</div>
<?php
render_footer();
