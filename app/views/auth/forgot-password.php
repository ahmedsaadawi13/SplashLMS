<?php
// FILE: /app/views/auth/forgot-password.php
$pageTitle = 'Forgot Password';
ob_start();
?>

<div class="card" style="max-width: 500px; margin: 3rem auto;">
    <h1 class="card-title">Reset Your Password</h1>
    <p>Enter your email address and we'll send you instructions to reset your password.</p>

    <form method="POST" action="<?= url('forgot-password') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Instructions</button>
    </form>

    <p class="text-center mt-3">
        <a href="<?= url('login') ?>">Back to Login</a>
    </p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
