<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

if (current_user()) {
    redirect(dashboard_path(current_user()['role']));
}

$errors = [];
$fields = [
    'full_name' => '', 'email' => '', 'nic' => '', 'phone' => '',
    'gender' => 'female', 'ward' => '', 'department' => '',
    'designation' => 'Staff Nurse', 'address' => '', 'date_of_joining' => '',
];

if (is_post()) {
    verify_csrf();
    foreach ($fields as $key => $_) {
        $fields[$key] = post_str($key);
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if ($fields['full_name'] === '' || strlen($fields['full_name']) < 3) {
        $errors[] = 'Enter your full name.';
    }
    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($fields['nic'] === '') {
        $errors[] = 'NIC is required.';
    }
    if ($fields['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }
    if ($fields['ward'] === '' || $fields['department'] === '') {
        $errors[] = 'Ward and department are required.';
    }
    if ($fields['date_of_joining'] === '') {
        $errors[] = 'Date of joining is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => strtolower($fields['email'])]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $userStmt = $pdo->prepare(
                'INSERT INTO users (email, password_hash, role, status)
                 VALUES (:email, :hash, \'nurse\', \'active\')'
            );
            $userStmt->execute([
                'email' => strtolower($fields['email']),
                'hash'  => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) $pdo->lastInsertId();

            $nurseStmt = $pdo->prepare(
                'INSERT INTO nurses
                 (user_id, employee_no, full_name, nic, gender, phone, ward, department, designation, address, date_of_joining)
                 VALUES
                 (:uid, :emp, :name, :nic, :gender, :phone, :ward, :dept, :desig, :addr, :doj)'
            );
            $emp = next_employee_no();
            $nurseStmt->execute([
                'uid'    => $userId,
                'emp'    => $emp,
                'name'   => $fields['full_name'],
                'nic'    => $fields['nic'],
                'gender' => in_array($fields['gender'], ['female', 'male', 'other'], true) ? $fields['gender'] : 'female',
                'phone'  => $fields['phone'],
                'ward'   => $fields['ward'],
                'dept'   => $fields['department'],
                'desig'  => $fields['designation'] !== '' ? $fields['designation'] : 'Staff Nurse',
                'addr'   => $fields['address'] !== '' ? $fields['address'] : null,
                'doj'    => $fields['date_of_joining'],
            ]);
            $nurseId = (int) $pdo->lastInsertId();
            ensure_balance($nurseId);
            $pdo->commit();

            login_user(['id' => $userId, 'role' => 'nurse']);
            flash_set('success', 'Registration complete. Your employee number is ' . $emp . '.');
            redirect('nurse/dashboard.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

render_header('Nurse registration', false);
?>
<div class="auth-card auth-wide">
    <h2>Nurse registration</h2>
    <p class="muted">Create your staff account. You can edit personal details later from your profile.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" class="form grid-2" novalidate>
        <?= csrf_field() ?>
        <label>Full name
            <input name="full_name" value="<?= e($fields['full_name']) ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($fields['email']) ?>" required>
        </label>
        <label>NIC
            <input name="nic" value="<?= e($fields['nic']) ?>" required>
        </label>
        <label>Phone
            <input name="phone" value="<?= e($fields['phone']) ?>" required>
        </label>
        <label>Gender
            <select name="gender">
                <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $fields['gender'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date of joining
            <input type="date" name="date_of_joining" value="<?= e($fields['date_of_joining']) ?>" required>
        </label>
        <label>Ward
            <input name="ward" value="<?= e($fields['ward']) ?>" required>
        </label>
        <label>Department
            <input name="department" value="<?= e($fields['department']) ?>" required>
        </label>
        <label>Designation
            <input name="designation" value="<?= e($fields['designation']) ?>">
        </label>
        <label>Address
            <input name="address" value="<?= e($fields['address']) ?>">
        </label>
        <label>Password
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Confirm password
            <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        </label>
        <div class="form-span">
            <button class="btn btn-block" type="submit">Create account</button>
        </div>
    </form>
    <p class="muted">Already registered? <a href="<?= e(url('auth/login.php')) ?>">Sign in</a></p>
</div>
<?php
render_footer(false);
