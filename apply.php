<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['nurse']);
$nurse = current_nurse();
if (!$nurse) {
    flash_set('error', 'Nurse profile is missing.');
    redirect('auth/logout.php');
}
$balance = ensure_balance((int) $nurse['id']);
$error = '';
$type = post_str('leave_type') ?: 'annual';
$start = post_str('start_date');
$end = post_str('end_date');
$reason = post_str('reason');
$medical = post_str('medical_note');

if (is_post()) {
    verify_csrf();
    $result = apply_leave((int) $nurse['id'], $type, $start, $end, $reason, $medical);
    if ($result['ok']) {
        flash_set('success', 'Leave application submitted for ' . $result['days'] . ' day(s).');
        redirect('nurse/applications.php');
    }
    $error = $result['error'];
}

render_header('Apply for leave');
?>
<div class="split">
    <div class="panel">
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form" id="leave-form">
            <?= csrf_field() ?>
            <label>Leave type
                <select name="leave_type" id="leave_type">
                    <?php foreach (['annual' => 'Annual leave', 'casual' => 'Casual leave', 'medical' => 'Medical leave'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $type === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="grid-2">
                <label>Start date<input type="date" name="start_date" id="start_date" value="<?= e($start) ?>" required></label>
                <label>End date<input type="date" name="end_date" id="end_date" value="<?= e($end) ?>" required></label>
            </div>
            <label>Reason<textarea name="reason" rows="4" required><?= e($reason) ?></textarea></label>
            <label id="medical-wrap">Medical note / certificate reference
                <input name="medical_note" value="<?= e($medical) ?>" placeholder="Optional certificate number or clinic name">
            </label>
            <button class="btn" type="submit">Submit application</button>
        </form>
    </div>
    <aside class="panel">
        <h2>Your remaining days (<?= (int) date('Y') ?>)</h2>
        <ul class="entitlements">
            <li><span>Annual</span><strong><?= remaining_days($balance, 'annual') ?></strong></li>
            <li><span>Casual</span><strong><?= remaining_days($balance, 'casual') ?></strong></li>
            <li><span>Medical</span><strong><?= remaining_days($balance, 'medical') ?></strong></li>
        </ul>
        <p class="muted">Inclusive calendar days are counted. Overlapping pending or approved leave is not allowed.</p>
    </aside>
</div>
<?php
render_footer();
