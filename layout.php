<?php
declare(strict_types=1);

function render_header(string $title, bool $appShell = true): void
{
    $user = current_user();
    $flash = flash_get();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · <?= e(APP_SHORT) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="<?= $appShell && $user ? 'app-body' : 'public-body' ?>">
<?php if ($appShell && $user): ?>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-mark">✚</span>
            <div>
                <strong><?= e(APP_SHORT) ?></strong>
                <small>Leave Register</small>
            </div>
        </div>
        <nav class="nav">
            <?php if ($user['role'] === 'nurse'): ?>
                <a href="<?= e(url('nurse/dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(url('nurse/apply.php')) ?>">Apply for leave</a>
                <a href="<?= e(url('nurse/applications.php')) ?>">My applications</a>
                <a href="<?= e(url('nurse/profile.php')) ?>">My profile</a>
            <?php elseif ($user['role'] === 'matron'): ?>
                <a href="<?= e(url('matron/dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(url('matron/applications.php')) ?>">Leave applications</a>
                <a href="<?= e(url('matron/nurses.php')) ?>">Nursing staff</a>
            <?php else: ?>
                <a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(url('admin/nurses.php')) ?>">Nurses</a>
                <a href="<?= e(url('admin/matrons.php')) ?>">Matrons</a>
                <a href="<?= e(url('admin/users.php')) ?>">Users</a>
                <a href="<?= e(url('admin/leaves.php')) ?>">Leave records</a>
                <a href="<?= e(url('admin/balances.php')) ?>">Leave balances</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-foot">
            <span class="role-pill"><?= e(ucfirst($user['role'])) ?></span>
            <a class="logout" href="<?= e(url('auth/logout.php')) ?>">Sign out</a>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="menu-btn" type="button" data-toggle-sidebar aria-label="Open menu">☰</button>
            <h1><?= e($title) ?></h1>
            <div class="userchip">
                <span><?= e(display_name($user)) ?></span>
                <small><?= e($user['email']) ?></small>
            </div>
        </header>
        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
<?php else: ?>
<div class="public">
    <header class="public-nav">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <span class="brand-mark">✚</span>
            <span><?= e(APP_NAME) ?></span>
        </a>
        <div class="public-links">
            <a href="<?= e(url('auth/login.php')) ?>">Sign in</a>
            <a class="btn btn-sm" href="<?= e(url('auth/register.php')) ?>">Nurse registration</a>
        </div>
    </header>
    <main class="public-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
<?php endif;
}

function render_footer(bool $appShell = true): void
{
    $user = current_user();
    ?>
<?php if ($appShell && $user): ?>
        </div>
    </div>
</div>
<?php else: ?>
    </main>
    <footer class="public-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?> · For authorised hospital staff</footer>
</div>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
<?php
}
