<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['matron']);

$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT n.*, u.email, u.status
        FROM nurses n JOIN users u ON u.id = n.user_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE n.full_name LIKE :q OR n.employee_no LIKE :q OR n.ward LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY n.full_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$year = (int) date('Y');

render_header('Nursing staff');
?>
<form class="filters" method="get">
    <input name="q" value="<?= e($q) ?>" placeholder="Search name, employee no, ward">
    <button class="btn btn-sm" type="submit">Search</button>
</form>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Employee</th><th>Ward</th><th>Contact</th><th>Annual left</th><th>Casual left</th><th>Medical left</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row):
                $b = ensure_balance((int) $row['id'], $year);
            ?>
                <tr>
                    <td><?= e($row['full_name']) ?><br><small><?= e($row['employee_no']) ?> · <?= e($row['designation']) ?></small></td>
                    <td><?= e($row['ward']) ?><br><small><?= e($row['department']) ?></small></td>
                    <td><?= e($row['phone']) ?><br><small><?= e($row['email']) ?></small></td>
                    <td><?= remaining_days($b, 'annual') ?></td>
                    <td><?= remaining_days($b, 'casual') ?></td>
                    <td><?= remaining_days($b, 'medical') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
