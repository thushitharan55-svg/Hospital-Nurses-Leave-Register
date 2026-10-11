<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['admin']);

$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$q = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT a.*, n.full_name, n.employee_no, n.ward
        FROM leave_applications a
        JOIN nurses n ON n.id = a.nurse_id
        WHERE 1=1';
$params = [];
if (in_array($type, ['annual', 'casual', 'medical'], true)) {
    $sql .= ' AND a.leave_type = :type';
    $params['type'] = $type;
}
if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
    $sql .= ' AND a.status = :status';
    $params['status'] = $status;
}
if ($q !== '') {
    $sql .= ' AND (n.full_name LIKE :q OR n.employee_no LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY a.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Leave records');
?>
<form class="filters" method="get">
    <input name="q" value="<?= e($q) ?>" placeholder="Nurse name or number">
    <select name="type">
        <option value="">All types</option>
        <?php foreach (['annual','casual','medical'] as $t): ?>
            <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= e(leave_label($t)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm" type="submit">Filter</button>
</form>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nurse</th><th>Type</th><th>Period</th><th>Days</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?><br><small><?= e($row['employee_no']) ?></small></td>
                    <td><?= e(leave_label($row['leave_type'])) ?></td>
                    <td><?= e(format_date($row['start_date'])) ?> – <?= e(format_date($row['end_date'])) ?></td>
                    <td><?= (int) $row['days'] ?></td>
                    <td><span class="badge <?= e(status_class($row['status'])) ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                    <td><a href="<?= e(url('matron/review.php?id=' . (int) $row['id'])) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
