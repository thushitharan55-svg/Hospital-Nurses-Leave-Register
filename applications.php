<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['nurse']);
$nurse = current_nurse();

if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['application_id'] ?? 0);
    $result = cancel_own_leave($id, (int) $nurse['id']);
    flash_set($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Application cancelled.' : $result['error']);
    redirect('nurse/applications.php');
}

$stmt = db()->prepare('SELECT * FROM leave_applications WHERE nurse_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $nurse['id']]);
$rows = $stmt->fetchAll();

render_header('My applications');
?>
<div class="panel">
    <?php if (!$rows): ?>
        <p class="muted">No applications yet. <a href="<?= e(url('nurse/apply.php')) ?>">Apply for leave</a>.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e(leave_label($row['leave_type'])) ?></td>
                        <td><?= e(format_date($row['start_date'])) ?> – <?= e(format_date($row['end_date'])) ?></td>
                        <td><?= (int) $row['days'] ?></td>
                        <td><?= e($row['reason']) ?></td>
                        <td><span class="badge <?= e(status_class($row['status'])) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <form method="post" data-confirm="Cancel this pending application?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="application_id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" type="submit">Cancel</button>
                                </form>
                            <?php elseif ($row['review_comment']): ?>
                                <small class="muted"><?= e($row['review_comment']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
render_footer();
