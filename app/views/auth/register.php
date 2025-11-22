<?php
// FILE: /app/views/auth/register.php
$pageTitle = 'Register';
ob_start();
?>

<div class="card" style="max-width: 600px; margin: 3rem auto;">
    <h1 class="card-title">Create Your SplashLMS Account</h1>

    <form method="POST" action="<?= url('register') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Organization Name *</label>
            <input type="text" name="tenant_name" class="form-control" placeholder="e.g., My Academy" required>
            <small>This will be your organization/academy name</small>
        </div>

        <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <small>Minimum 8 characters</small>
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
    </form>

    <p class="text-center mt-3">
        Already have an account? <a href="<?= url('login') ?>">Login here</a>
    </p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
