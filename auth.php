<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $stmt = db()->prepare(
        'SELECT id, email, role, status FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') {
        logout_user();
        $user = null;
        return null;
    }
    $user = $row;
    return $user;
}

function current_nurse(): ?array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'nurse') {
        return null;
    }
    static $nurse = false;
    if ($nurse !== false) {
        return $nurse;
    }
    $stmt = db()->prepare('SELECT * FROM nurses WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => (int) $user['id']]);
    $nurse = $stmt->fetch() ?: null;
    return $nurse;
}

function current_matron(): ?array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'matron') {
        return null;
    }
    static $matron = false;
    if ($matron !== false) {
        return $matron;
    }
    $stmt = db()->prepare('SELECT * FROM matrons WHERE user_id = :uid LIMIT 1');
    $stmt->execute(['uid' => (int) $user['id']]);
    $matron = $stmt->fetch() ?: null;
    return $matron;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash_set('error', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash_set('error', 'You do not have permission to access that page.');
        redirect(dashboard_path($user['role']));
    }
    return $user;
}

function dashboard_path(string $role): string
{
    return match ($role) {
        'admin'  => 'admin/dashboard.php',
        'matron' => 'matron/dashboard.php',
        default  => 'nurse/dashboard.php',
    };
}

function display_name(array $user): string
{
    if ($user['role'] === 'nurse') {
        $n = current_nurse();
        return $n['full_name'] ?? $user['email'];
    }
    if ($user['role'] === 'matron') {
        $m = current_matron();
        return $m['full_name'] ?? $user['email'];
    }
    return 'Administrator';
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

function authenticate(string $email, string $password): ?array
{
    $stmt = db()->prepare('SELECT id, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    if ($user['status'] !== 'active') {
        return ['inactive' => true];
    }
    unset($user['password_hash']);
    return $user;
}

function next_employee_no(): string
{
    $year = date('Y');
    $stmt = db()->prepare(
        'SELECT employee_no FROM nurses WHERE employee_no LIKE :prefix ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute(['prefix' => 'NLS-' . $year . '-%']);
    $last = $stmt->fetchColumn();
    $seq = 1;
    if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
        $seq = (int) $m[1] + 1;
    }
    return sprintf('NLS-%s-%04d', $year, $seq);
}
