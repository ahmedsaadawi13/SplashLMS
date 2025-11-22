<?php
// FILE: /app/views/home/index.php
$pageTitle = 'Welcome';
ob_start();
?>

<div class="text-center" style="padding: 4rem 0;">
    <h1 style="font-size: 3rem; margin-bottom: 1rem;">Welcome to SplashLMS</h1>
    <p style="font-size: 1.25rem; color: #6c757d; margin-bottom: 2rem;">
        The complete multi-tenant Learning Management System for online courses
    </p>

    <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="<?= url('register') ?>" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.125rem;">
            Get Started Free
        </a>
        <a href="<?= url('login') ?>" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1.125rem;">
            Login
        </a>
    </div>
</div>

<div class="grid" style="margin-top: 4rem;">
    <div class="card text-center">
        <h3>📚 Course Management</h3>
        <p>Create and manage courses with sections, lessons, quizzes, and certificates.</p>
    </div>

    <div class="card text-center">
        <h3>👥 Multi-Tenant</h3>
        <p>Each organization gets their own isolated workspace with custom branding.</p>
    </div>

    <div class="card text-center">
        <h3>📊 Analytics</h3>
        <p>Track student progress, course completion rates, and revenue metrics.</p>
    </div>
</div>

<div class="card" style="margin-top: 4rem;">
    <h2 class="card-title text-center">Features</h2>

    <ul style="max-width: 800px; margin: 0 auto; font-size: 1.125rem; line-height: 2;">
        <li>Complete course builder with sections and lessons</li>
        <li>Support for video, text, and downloadable lessons</li>
        <li>Quiz system with multiple-choice questions</li>
        <li>Automatic certificate generation</li>
        <li>Subscription plans with quota enforcement</li>
        <li>Student enrollment and progress tracking</li>
        <li>RESTful API for external integrations</li>
        <li>Secure multi-tenant architecture</li>
    </ul>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
