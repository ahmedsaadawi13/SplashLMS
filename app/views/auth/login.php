<?php
// FILE: /app/views/auth/login.php
$pageTitle = 'Login';
ob_start();
?>

<div class="card" style="max-width: 500px; margin: 3rem auto;">
    <h1 class="card-title">Login to SplashLMS</h1>

    <form method="POST" action="<?= url('login') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Organization Slug (optional for students)</label>
            <input type="text" name="tenant_slug" class="form-control" placeholder="e.g., tech-academy">
            <small>Leave blank if you are a platform admin</small>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
    </form>

    <p class="text-center mt-3">
        Don't have an account? <a href="<?= url('register') ?>">Register here</a>
    </p>

    <p class="text-center">
        <a href="<?= url('forgot-password') ?>">Forgot password?</a>
    </p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
