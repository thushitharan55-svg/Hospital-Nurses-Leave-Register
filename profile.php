<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role(['nurse']);
$nurse = current_nurse();
if (!$nurse) {
    flash_set('error', 'Profile not found.');
    redirect('nurse/dashboard.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $fullName = post_str('full_name');
    $nic = post_str('nic');
    $phone = post_str('phone');
    $gender = post_str('gender');
    $ward = post_str('ward');
    $department = post_str('department');
    $designation = post_str('designation');
    $address = post_str('address');
    $doj = post_str('date_of_joining');
    $newPass = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['new_password_confirm'] ?? '');

    if (strlen($fullName) < 3) {
        $errors[] = 'Full name is required.';
    }
    if ($nic === '' || $phone === '' || $ward === '' || $department === '' || $doj === '') {
        $errors[] = 'NIC, phone, ward, department and date of joining are required.';
    }
    if ($newPass !== '' && strlen($newPass) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if ($newPass !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        $upd = db()->prepare(
            'UPDATE nurses SET full_name = :name, nic = :nic, phone = :phone, gender = :gender,
             ward = :ward, department = :dept, designation = :desig, address = :addr, date_of_joining = :doj
             WHERE id = :id AND user_id = :uid'
        );
        $upd->execute([
            'name'  => $fullName,
            'nic'   => $nic,
            'phone' => $phone,
            'gender'=> in_array($gender, ['female', 'male', 'other'], true) ? $gender : $nurse['gender'],
            'ward'  => $ward,
            'dept'  => $department,
            'desig' => $designation !== '' ? $designation : 'Staff Nurse',
            'addr'  => $address !== '' ? $address : null,
            'doj'   => $doj,
            'id'    => $nurse['id'],
            'uid'   => $user['id'],
        ]);

        if ($newPass !== '') {
            $p = db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
            $p->execute(['h' => password_hash($newPass, PASSWORD_DEFAULT), 'id' => $user['id']]);
        }

        flash_set('success', 'Profile updated.');
        redirect('nurse/profile.php');
    }
    $nurse = array_merge($nurse, [
        'full_name' => $fullName, 'nic' => $nic, 'phone' => $phone, 'gender' => $gender,
        'ward' => $ward, 'department' => $department, 'designation' => $designation,
        'address' => $address, 'date_of_joining' => $doj,
    ]);
}

render_header('My profile');
?>
<div class="panel">
    <p class="muted">Employee number <strong><?= e($nurse['employee_no']) ?></strong> cannot be changed. You may only edit your own record.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="form grid-2">
        <?= csrf_field() ?>
        <label>Full name<input name="full_name" value="<?= e($nurse['full_name']) ?>" required></label>
        <label>Email<input value="<?= e($user['email']) ?>" disabled></label>
        <label>NIC<input name="nic" value="<?= e($nurse['nic']) ?>" required></label>
        <label>Phone<input name="phone" value="<?= e($nurse['phone']) ?>" required></label>
        <label>Gender
            <select name="gender">
                <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $nurse['gender'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date of joining<input type="date" name="date_of_joining" value="<?= e($nurse['date_of_joining']) ?>" required></label>
        <label>Ward<input name="ward" value="<?= e($nurse['ward']) ?>" required></label>
        <label>Department<input name="department" value="<?= e($nurse['department']) ?>" required></label>
        <label>Designation<input name="designation" value="<?= e($nurse['designation']) ?>"></label>
        <label>Address<input name="address" value="<?= e($nurse['address'] ?? '') ?>"></label>
        <label>New password <small>(leave blank to keep current)</small>
            <input type="password" name="new_password" minlength="8" autocomplete="new-password">
        </label>
        <label>Confirm new password
            <input type="password" name="new_password_confirm" minlength="8" autocomplete="new-password">
        </label>
        <div class="form-span"><button class="btn" type="submit">Save profile</button></div>
    </form>
</div>
<?php
render_footer();
