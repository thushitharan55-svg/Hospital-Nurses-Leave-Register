<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

if (current_user()) {
    redirect(dashboard_path(current_user()['role']));
}

$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email = strtolower(post_str('email'));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $user = authenticate($email, $password);
        if ($user === null) {
            $error = 'Invalid email or password.';
        } elseif (!empty($user['inactive'])) {
            $error = 'This account is inactive. Contact administration.';
        } else {
            login_user($user);
            flash_set('success', 'Welcome back.');
            redirect(dashboard_path($user['role']));
        }
    }
}

render_header('Sign in', false);
?>
<div class="auth-card">
    <h2>Staff sign in</h2>
    <p class="muted">Use your hospital email and password.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username">
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn-block" type="submit">Sign in</button>
    </form>
    <p class="muted">New nurse? <a href="<?= e(url('auth/register.php')) ?>">Register here</a></p>
</div>
<?php
render_footer(false);
