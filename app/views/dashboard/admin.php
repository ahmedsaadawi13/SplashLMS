<?php
// FILE: /app/views/dashboard/admin.php
$pageTitle = 'Admin Dashboard';
ob_start();
?>

<h1>Dashboard - <?= e($tenant['name'] ?? 'Admin') ?></h1>

<div class="grid" style="grid-template-columns: repeat(4, 1fr); margin-top: 2rem;">
    <div class="card">
        <h3>Total Courses</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #007bff;"><?= $totalCourses ?></p>
    </div>
    <div class="card">
        <h3>Total Students</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?= $totalStudents ?></p>
    </div>
    <div class="card">
        <h3>Total Enrollments</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?= $totalEnrollments ?></p>
    </div>
    <div class="card">
        <h3>Revenue</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?= formatCurrency($revenue) ?></p>
    </div>
</div>

<?php if (isset($tenant['subscription_status'])): ?>
    <div class="card mt-4">
        <h2 class="card-title">Subscription & Limits</h2>
        <p><strong>Plan:</strong> <?= e($tenant['plan_name'] ?? 'N/A') ?></p>
        <p><strong>Status:</strong> <span class="badge badge-<?= $tenant['subscription_status'] === 'active' ? 'success' : 'warning' ?>">
            <?= ucfirst($tenant['subscription_status'] ?? 'N/A') ?>
        </span></p>
        <p><strong>Limits:</strong>
            <?= $totalCourses ?> / <?= $tenant['max_courses'] ?> courses,
            <?= $totalStudents ?> / <?= $tenant['max_students'] ?> students,
            <?= $totalEnrollments ?> / <?= $tenant['max_enrollments'] ?> enrollments
        </p>
        <a href="<?= url('admin/subscription') ?>" class="btn btn-primary btn-sm">Manage Subscription</a>
    </div>
<?php endif; ?>

<div class="card mt-4">
    <h2 class="card-title">Recent Courses</h2>
    <?php if (empty($courses)): ?>
        <p>No courses yet.</p>
        <a href="<?= url('admin/courses/create') ?>" class="btn btn-primary">Create Your First Course</a>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Enrollments</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?= e($course['title']) ?></td>
                        <td><?= e($course['category_name'] ?? 'N/A') ?></td>
                        <td><span class="badge badge-<?= $course['status'] === 'published' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($course['status']) ?>
                        </span></td>
                        <td><?= $course['enrollments_count'] ?? 0 ?></td>
                        <td>
                            <a href="<?= url('admin/courses/' . $course['id'] . '/edit') ?>" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="<?= url('admin/courses') ?>" class="btn btn-secondary">View All Courses</a>
    <?php endif; ?>
</div>

<div class="card mt-4">
    <h2 class="card-title">Recent Enrollments</h2>
    <?php if (empty($recentEnrollments)): ?>
        <p>No enrollments yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Progress</th>
                    <th>Enrolled Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentEnrollments as $enrollment): ?>
                    <tr>
                        <td><?= e($enrollment['student_name']) ?></td>
                        <td><?= e($enrollment['course_title']) ?></td>
                        <td><?= round($enrollment['progress_percent']) ?>%</td>
                        <td><?= formatDate($enrollment['enrolled_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
