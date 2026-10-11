<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid security token. Please refresh and try again.');
    }
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function remember_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function leave_label(string $type): string
{
    return match ($type) {
        'annual'  => 'Annual Leave',
        'casual'  => 'Casual Leave',
        'medical' => 'Medical Leave',
        default   => ucfirst($type),
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'cancelled' => 'badge-muted',
        default    => 'badge-warning',
    };
}

function inclusive_days(string $start, string $end): int
{
    $from = new DateTimeImmutable($start);
    $to = new DateTimeImmutable($end);
    return (int) $from->diff($to)->days + 1;
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date)
        ?: DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format('d M Y') : $date;
}

function post_str(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}
