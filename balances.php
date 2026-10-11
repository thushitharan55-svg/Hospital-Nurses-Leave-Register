<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['admin']);

$year = (int) ($_GET['year'] ?? date('Y'));

if (is_post()) {
    verify_csrf();
    $bid = (int) ($_POST['balance_id'] ?? 0);
    $fields = ['annual_entitled','annual_used','casual_entitled','casual_used','medical_entitled','medical_used'];
    $set = [];
    $params = ['id' => $bid];
    foreach ($fields as $f) {
        $set[] = "$f = :$f";
        $params[$f] = max(0, (int) ($_POST[$f] ?? 0));
    }
    $sql = 'UPDATE leave_balances SET ' . implode(', ', $set) . ' WHERE id = :id';
    db()->prepare($sql)->execute($params);
    flash_set('success', 'Balance updated.');
    redirect('admin/balances.php?year=' . $year);
}

$stmt = db()->prepare(
    'SELECT b.*, n.full_name, n.employee_no, n.ward
     FROM leave_balances b
     JOIN nurses n ON n.id = b.nurse_id
     WHERE b.year = :year
     ORDER BY n.full_name'
);
$stmt->execute(['year' => $year]);
$rows = $stmt->fetchAll();

$nurses = db()->query('SELECT id FROM nurses')->fetchAll();
foreach ($nurses as $n) {
    ensure_balance((int) $n['id'], $year);
}
if (!$rows && $nurses) {
    $stmt->execute(['year' => $year]);
    $rows = $stmt->fetchAll();
}

render_header('Leave balances');
?>
<form class="filters" method="get">
    <label>Year <input type="number" name="year" value="<?= $year ?>" min="2020" max="2100"></label>
    <button class="btn btn-sm" type="submit">Load</button>
</form>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nurse</th>
                    <th>Annual (used / entitled)</th>
                    <th>Casual</th>
                    <th>Medical</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?><br><small><?= e($row['employee_no']) ?></small></td>
                    <td>
                        <form method="post" class="inline-balance">
                            <?= csrf_field() ?>
                            <input type="hidden" name="balance_id" value="<?= (int) $row['id'] ?>">
                            <input type="number" name="annual_used" value="<?= (int) $row['annual_used'] ?>" min="0">
                            /
                            <input type="number" name="annual_entitled" value="<?= (int) $row['annual_entitled'] ?>" min="0">
                    </td>
                    <td>
                            <input type="number" name="casual_used" value="<?= (int) $row['casual_used'] ?>" min="0">
                            /
                            <input type="number" name="casual_entitled" value="<?= (int) $row['casual_entitled'] ?>" min="0">
                    </td>
                    <td>
                            <input type="number" name="medical_used" value="<?= (int) $row['medical_used'] ?>" min="0">
                            /
                            <input type="number" name="medical_entitled" value="<?= (int) $row['medical_entitled'] ?>" min="0">
                    </td>
                    <td><button class="btn btn-sm" type="submit">Save</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_footer();
