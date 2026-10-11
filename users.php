<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$me = require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $uid = (int) ($_POST['user_id'] ?? 0);
    $action = post_str('action');
    if ($uid === (int) $me['id']) {
        flash_set('error', 'You cannot change your own account status here.');
        redirect('admin/users.php');
    }
    if ($action === 'toggle') {
        $stmt = db()->prepare('SELECT status FROM users WHERE id = :id');
        $stmt->execute(['id' => $uid]);
        $status = $stmt->fetchColumn();
        if ($status) {
            $next = $status === 'active' ? 'inactive' : 'active';
            $upd = db()->prepare('UPDATE users SET status = :s WHERE id = :id');
            $upd->execute(['s' => $next, 'id' => $uid]);
            flash_set('success', 'User status updated.');
        }
    }
    redirect('admin/users.php');
}

$rows = db()->query(
    'SELECT u.id, u.email, u.role, u.status, u.created_at,
            COALESCE(n.full_name, m.full_name, \'Administrator\') AS display_name
     FROM users u
     LEFT JOIN nurses n ON n.user_id = u.id
     LEFT JOIN matrons m ON m.user_id = u.id
     ORDER BY u.role, u.email'
)->fetchAll();

render_header('Manage users');
?>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['display_name']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><?= e(ucfirst($row['role'])) ?></td>
                    <td><span class="badge <?= $row['status']==='active'?'badge-success':'badge-muted' ?>"><?= e($row['status']) ?></span></td>
                    <td><?= e(format_date($row['created_at'])) ?></td>
                    <td>
                        <?php if ((int) $row['id'] !== (int) $me['id']): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-ghost btn-sm" type="submit">
                                    <?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <small class="muted">You</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
