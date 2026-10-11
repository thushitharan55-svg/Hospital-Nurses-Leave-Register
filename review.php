<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role(['matron', 'admin']);
$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT a.*, n.full_name, n.employee_no, n.ward, n.department, n.phone, n.designation
     FROM leave_applications a
     JOIN nurses n ON n.id = a.nurse_id
     WHERE a.id = :id LIMIT 1'
);
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();
if (!$app) {
    flash_set('error', 'Application not found.');
    redirect($user['role'] === 'admin' ? 'admin/leaves.php' : 'matron/applications.php');
}

$balance = ensure_balance((int) $app['nurse_id'], (int) substr($app['start_date'], 0, 4));
$error = '';

if (is_post() && $app['status'] === 'pending') {
    verify_csrf();
    $decision = post_str('decision');
    $comment = post_str('review_comment');
    $result = decide_leave($id, $decision, (int) $user['id'], $comment);
    if ($result['ok']) {
        flash_set('success', 'Application ' . $decision . '.');
        redirect($user['role'] === 'admin' ? 'admin/leaves.php' : 'matron/applications.php');
    }
    $error = $result['error'];
}

$back = $user['role'] === 'admin' ? 'admin/leaves.php' : 'matron/applications.php';
render_header('Review application');
?>
<div class="split">
    <div class="panel">
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <dl class="meta">
            <div><dt>Nurse</dt><dd><?= e($app['full_name']) ?> (<?= e($app['employee_no']) ?>)</dd></div>
            <div><dt>Ward / Dept</dt><dd><?= e($app['ward']) ?> · <?= e($app['department']) ?></dd></div>
            <div><dt>Designation</dt><dd><?= e($app['designation']) ?></dd></div>
            <div><dt>Phone</dt><dd><?= e($app['phone']) ?></dd></div>
            <div><dt>Type</dt><dd><?= e(leave_label($app['leave_type'])) ?></dd></div>
            <div><dt>Period</dt><dd><?= e(format_date($app['start_date'])) ?> – <?= e(format_date($app['end_date'])) ?> (<?= (int) $app['days'] ?> days)</dd></div>
            <div class="span"><dt>Reason</dt><dd><?= e($app['reason']) ?></dd></div>
            <?php if ($app['medical_note']): ?>
                <div class="span"><dt>Medical note</dt><dd><?= e($app['medical_note']) ?></dd></div>
            <?php endif; ?>
            <div><dt>Status</dt><dd><span class="badge <?= e(status_class($app['status'])) ?>"><?= e(ucfirst($app['status'])) ?></span></dd></div>
            <?php if ($app['review_comment']): ?>
                <div class="span"><dt>Review comment</dt><dd><?= e($app['review_comment']) ?></dd></div>
            <?php endif; ?>
        </dl>
        <?php if ($app['status'] === 'pending'): ?>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <label>Comment <small>(optional)</small>
                    <textarea name="review_comment" rows="3"></textarea>
                </label>
                <div class="btn-row">
                    <button class="btn" name="decision" value="approved" type="submit" data-confirm="Approve this leave and deduct from balance?">Approve</button>
                    <button class="btn btn-danger" name="decision" value="rejected" type="submit" data-confirm="Reject this application?">Reject</button>
                    <a class="btn btn-ghost" href="<?= e(url($back)) ?>">Back</a>
                </div>
            </form>
        <?php else: ?>
            <a class="btn btn-ghost" href="<?= e(url($back)) ?>">Back</a>
        <?php endif; ?>
    </div>
    <aside class="panel">
        <h2>Balance <?= e(substr($app['start_date'], 0, 4)) ?></h2>
        <ul class="entitlements">
            <li><span>Annual remaining</span><strong><?= remaining_days($balance, 'annual') ?></strong></li>
            <li><span>Casual remaining</span><strong><?= remaining_days($balance, 'casual') ?></strong></li>
            <li><span>Medical remaining</span><strong><?= remaining_days($balance, 'medical') ?></strong></li>
        </ul>
    </aside>
</div>
<?php
render_footer();
