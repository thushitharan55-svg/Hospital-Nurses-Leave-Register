<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['nurse']);
$nurse = current_nurse();
if (!$nurse) {
    flash_set('error', 'Nurse profile is missing. Contact administration.');
    redirect('auth/logout.php');
}

$balance = ensure_balance((int) $nurse['id']);
$stmt = db()->prepare(
    'SELECT * FROM leave_applications WHERE nurse_id = :id ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute(['id' => $nurse['id']]);
$recent = $stmt->fetchAll();

$pending = db()->prepare('SELECT COUNT(*) FROM leave_applications WHERE nurse_id = :id AND status = \'pending\'');
$pending->execute(['id' => $nurse['id']]);

render_header('Nurse dashboard');
?>
<section class="stats">
    <?php foreach (['annual' => 'Annual', 'casual' => 'Casual', 'medical' => 'Medical'] as $type => $label): ?>
        <article class="stat-card">
            <h3><?= e($label) ?></h3>
            <p class="stat-num"><?= remaining_days($balance, $type) ?> <small>/ <?= (int) $balance[$type . '_entitled'] ?></small></p>
            <span>days remaining</span>
        </article>
    <?php endforeach; ?>
    <article class="stat-card">
        <h3>Pending</h3>
        <p class="stat-num"><?= (int) $pending->fetchColumn() ?></p>
        <span>awaiting matron review</span>
    </article>
</section>

<div class="panel">
    <div class="panel-head">
        <h2>Recent applications</h2>
        <a class="btn btn-sm" href="<?= e(url('nurse/apply.php')) ?>">New application</a>
    </div>
    <?php if (!$recent): ?>
        <p class="muted">You have not applied for leave yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e(leave_label($row['leave_type'])) ?></td>
                        <td><?= e(format_date($row['start_date'])) ?> – <?= e(format_date($row['end_date'])) ?></td>
                        <td><?= (int) $row['days'] ?></td>
                        <td><span class="badge <?= e(status_class($row['status'])) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
render_footer();
