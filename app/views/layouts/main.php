<?php
// FILE: /app/views/layouts/main.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>SplashLMS</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?= url('') ?>" class="logo">SplashLMS</a>
                <nav>
                    <ul class="nav">
                        <?php if (isAuth()): ?>
                            <li><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                            <?php if (hasRole('student')): ?>
                                <li><a href="<?= url('catalog') ?>">Browse Courses</a></li>
                                <li><a href="<?= url('my-courses') ?>">My Courses</a></li>
                            <?php endif; ?>
                            <?php if (hasRole(['tenant_admin', 'instructor'])): ?>
                                <li><a href="<?= url('admin/courses') ?>">Manage Courses</a></li>
                                <li><a href="<?= url('admin/analytics') ?>">Analytics</a></li>
                            <?php endif; ?>
                            <?php if (hasRole('platform_admin')): ?>
                                <li><a href="<?= url('platform/dashboard') ?>">Platform</a></li>
                            <?php endif; ?>
                            <li><a href="<?= url('logout') ?>">Logout (<?= e(auth()['name']) ?>)</a></li>
                        <?php else: ?>
                            <li><a href="<?= url('login') ?>">Login</a></li>
                            <li><a href="<?= url('register') ?>">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if ($successMsg = flash('success')): ?>
                <div class="alert alert-success"><?= e($successMsg) ?></div>
            <?php endif; ?>

            <?php if ($errorMsg = flash('error')): ?>
                <div class="alert alert-error"><?= $errorMsg ?></div>
            <?php endif; ?>

            <?php if ($warningMsg = flash('warning')): ?>
                <div class="alert alert-warning"><?= e($warningMsg) ?></div>
            <?php endif; ?>

            <?php if ($infoMsg = flash('info')): ?>
                <div class="alert alert-info"><?= e($infoMsg) ?></div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SplashLMS. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
