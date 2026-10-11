<?php
declare(strict_types=1);

function ensure_balance(int $nurseId, ?int $year = null): array
{
    $year = $year ?? (int) date('Y');
    $stmt = db()->prepare('SELECT * FROM leave_balances WHERE nurse_id = :nid AND year = :year LIMIT 1');
    $stmt->execute(['nid' => $nurseId, 'year' => $year]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }
    $ins = db()->prepare(
        'INSERT INTO leave_balances
         (nurse_id, year, annual_entitled, casual_entitled, medical_entitled)
         VALUES (:nid, :year, :annual, :casual, :medical)'
    );
    $ins->execute([
        'nid'     => $nurseId,
        'year'    => $year,
        'annual'  => LEAVE_ENTITLEMENTS['annual'],
        'casual'  => LEAVE_ENTITLEMENTS['casual'],
        'medical' => LEAVE_ENTITLEMENTS['medical'],
    ]);
    $stmt->execute(['nid' => $nurseId, 'year' => $year]);
    return $stmt->fetch();
}

function remaining_days(array $balance, string $type): int
{
    return match ($type) {
        'annual'  => (int) $balance['annual_entitled'] - (int) $balance['annual_used'],
        'casual'  => (int) $balance['casual_entitled'] - (int) $balance['casual_used'],
        'medical' => (int) $balance['medical_entitled'] - (int) $balance['medical_used'],
        default   => 0,
    };
}

function used_column(string $type): string
{
    return match ($type) {
        'annual'  => 'annual_used',
        'casual'  => 'casual_used',
        'medical' => 'medical_used',
        default   => throw new InvalidArgumentException('Invalid leave type'),
    };
}

function dates_overlap(int $nurseId, string $start, string $end, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM leave_applications
            WHERE nurse_id = :nid
              AND status IN (\'pending\', \'approved\')
              AND start_date <= :end
              AND end_date >= :start';
    $params = ['nid' => $nurseId, 'start' => $start, 'end' => $end];
    if ($ignoreId) {
        $sql .= ' AND id <> :id';
        $params['id'] = $ignoreId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

function apply_leave(int $nurseId, string $type, string $start, string $end, string $reason, ?string $medicalNote): array
{
    if (!in_array($type, ['annual', 'casual', 'medical'], true)) {
        return ['ok' => false, 'error' => 'Invalid leave type.'];
    }
    if ($start === '' || $end === '') {
        return ['ok' => false, 'error' => 'Start and end dates are required.'];
    }
    if ($end < $start) {
        return ['ok' => false, 'error' => 'End date cannot be before start date.'];
    }
    if ($reason === '') {
        return ['ok' => false, 'error' => 'Please provide a reason for leave.'];
    }
    $days = inclusive_days($start, $end);
    if ($days < 1 || $days > 90) {
        return ['ok' => false, 'error' => 'Leave duration is not valid.'];
    }
    if (dates_overlap($nurseId, $start, $end)) {
        return ['ok' => false, 'error' => 'These dates overlap an existing pending or approved leave.'];
    }

    $year = (int) substr($start, 0, 4);
    $balance = ensure_balance($nurseId, $year);
    if (remaining_days($balance, $type) < $days) {
        return ['ok' => false, 'error' => 'Insufficient ' . leave_label($type) . ' balance for this request.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO leave_applications
         (nurse_id, leave_type, start_date, end_date, days, reason, medical_note, status)
         VALUES (:nid, :type, :start, :end, :days, :reason, :note, \'pending\')'
    );
    $stmt->execute([
        'nid'    => $nurseId,
        'type'   => $type,
        'start'  => $start,
        'end'    => $end,
        'days'   => $days,
        'reason' => $reason,
        'note'   => $medicalNote ?: null,
    ]);

    return ['ok' => true, 'days' => $days];
}

function decide_leave(int $applicationId, string $decision, int $reviewerId, string $comment): array
{
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        return ['ok' => false, 'error' => 'Invalid decision.'];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT * FROM leave_applications WHERE id = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $applicationId]);
        $app = $stmt->fetch();
        if (!$app) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Application not found.'];
        }
        if ($app['status'] !== 'pending') {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'This application has already been reviewed.'];
        }

        if ($decision === 'approved') {
            $year = (int) substr($app['start_date'], 0, 4);
            $balance = ensure_balance((int) $app['nurse_id'], $year);
            $type = $app['leave_type'];
            if (remaining_days($balance, $type) < (int) $app['days']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Nurse does not have enough remaining leave.'];
            }
            $col = used_column($type);
            $upd = $pdo->prepare(
                "UPDATE leave_balances SET {$col} = {$col} + :days
                 WHERE nurse_id = :nid AND year = :year"
            );
            $upd->execute([
                'days' => (int) $app['days'],
                'nid'  => (int) $app['nurse_id'],
                'year' => $year,
            ]);
        }

        $updApp = $pdo->prepare(
            'UPDATE leave_applications
             SET status = :status, reviewed_by = :uid, reviewed_at = NOW(), review_comment = :comment
             WHERE id = :id'
        );
        $updApp->execute([
            'status'  => $decision,
            'uid'     => $reviewerId,
            'comment' => $comment !== '' ? $comment : null,
            'id'      => $applicationId,
        ]);

        $pdo->commit();
        return ['ok' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Unable to update the application.'];
    }
}

function cancel_own_leave(int $applicationId, int $nurseId): array
{
    $stmt = db()->prepare(
        'UPDATE leave_applications SET status = \'cancelled\'
         WHERE id = :id AND nurse_id = :nid AND status = \'pending\''
    );
    $stmt->execute(['id' => $applicationId, 'nid' => $nurseId]);
    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'error' => 'Only pending applications can be cancelled.'];
    }
    return ['ok' => true];
}
