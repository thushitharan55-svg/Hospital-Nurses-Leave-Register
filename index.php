<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = current_user();
if ($user) {
    redirect(dashboard_path($user['role']));
}

render_header('Home', false);
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Nursing Administration</p>
        <h2>Hospital Nurses Leave Register</h2>
        <p>Register online, manage your profile, apply for annual, casual, or medical leave, and track remaining balances. Matrons review applications; administrators oversee staff and records.</p>
        <div class="hero-actions">
            <a class="btn" href="<?= e(url('auth/register.php')) ?>">Register as a nurse</a>
            <a class="btn btn-outline" href="<?= e(url('auth/login.php')) ?>">Staff sign in</a>
        </div>
    </div>
    <div class="hero-panel">
        <h3>Leave entitlements (per year)</h3>
        <ul class="entitlements">
            <li><span>Annual leave</span><strong><?= (int) LEAVE_ENTITLEMENTS['annual'] ?> days</strong></li>
            <li><span>Casual leave</span><strong><?= (int) LEAVE_ENTITLEMENTS['casual'] ?> days</strong></li>
            <li><span>Medical leave</span><strong><?= (int) LEAVE_ENTITLEMENTS['medical'] ?> days</strong></li>
        </ul>
        <p class="muted">Balances are created automatically for the current calendar year. Approval deducts days from the nurse’s remaining entitlement.</p>
    </div>
</section>
<section class="features">
    <article>
        <h3>Nurses</h3>
        <p>Create an account, keep your personal record up to date, and submit leave only against remaining days.</p>
    </article>
    <article>
        <h3>Matron</h3>
        <p>Review pending applications, approve or reject with comments, and see ward staffing leave at a glance.</p>
    </article>
    <article>
        <h3>Administration</h3>
        <p>Manage nurses, matrons, user accounts, leave history, and yearly leave balances from one dashboard.</p>
    </article>
</section>
<?php
render_footer(false);
