<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['admin']);

$errors = [];
if (is_post() && post_str('action') === 'create') {
    verify_csrf();
    $name = post_str('full_name');
    $email = strtolower(post_str('email'));
    $phone = post_str('phone');
    $dept = post_str('department') ?: 'Nursing Administration';
    $password = (string) ($_POST['password'] ?? '');

    if (strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $errors[] = 'Name, valid email, and a password of at least 8 characters are required.';
    } else {
        $exists = db()->prepare('SELECT id FROM users WHERE email = :e');
        $exists->execute(['e' => $email]);
        if ($exists->fetch()) {
            $errors[] = 'Email already exists.';
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $u = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, role, status) VALUES (:e, :h, \'matron\', \'active\')'
                );
                $u->execute(['e' => $email, 'h' => password_hash($password, PASSWORD_DEFAULT)]);
                $m = $pdo->prepare(
                    'INSERT INTO matrons (user_id, full_name, phone, department) VALUES (:uid, :n, :p, :d)'
                );
                $m->execute([
                    'uid' => (int) $pdo->lastInsertId(),
                    'n' => $name,
                    'p' => $phone,
                    'd' => $dept,
                ]);
                $pdo->commit();
                flash_set('success', 'Matron account created.');
                redirect('admin/matrons.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Could not create matron.';
            }
        }
    }
}

$rows = db()->query(
    'SELECT m.*, u.email, u.status FROM matrons m JOIN users u ON u.id = m.user_id ORDER BY m.full_name'
)->fetchAll();

render_header('Manage matrons');
?>
<div class="split">
    <div class="panel">
        <h2>Matrons</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Department</th><th>Email</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?><br><small><?= e($row['phone']) ?></small></td>
                        <td><?= e($row['department']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><span class="badge <?= $row['status']==='active'?'badge-success':'badge-muted' ?>"><?= e($row['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted">Deactivate accounts from Users if a matron should no longer sign in.</p>
    </div>
    <aside class="panel">
        <h2>Add matron</h2>
        <?php if ($errors): ?><div class="alert alert-error"><?= e($errors[0]) ?></div><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label>Full name<input name="full_name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Phone<input name="phone" required></label>
            <label>Department<input name="department" value="Nursing Administration"></label>
            <label>Password<input type="password" name="password" required minlength="8"></label>
            <button class="btn" type="submit">Create matron</button>
        </form>
    </aside>
</div>
<?php
render_footer();
