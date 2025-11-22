<?php
// FILE: /app/views/dashboard/student.php
$pageTitle = 'Student Dashboard';
ob_start();
?>

<h1>Welcome, <?= e($user['name']) ?>!</h1>

<div class="grid" style="grid-template-columns: repeat(3, 1fr); margin-top: 2rem;">
    <div class="card">
        <h3>Total Enrollments</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #007bff;"><?= $totalEnrollments ?></p>
    </div>
    <div class="card">
        <h3>Completed Courses</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?= $completedCourses ?></p>
    </div>
    <div class="card">
        <h3>Average Progress</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?= $avgProgress ?>%</p>
    </div>
</div>

<div class="card mt-4">
    <h2 class="card-title">My Courses</h2>

    <?php if (empty($enrollments)): ?>
        <p>You are not enrolled in any courses yet.</p>
        <a href="<?= url('catalog') ?>" class="btn btn-primary">Browse Courses</a>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($enrollments as $enrollment): ?>
                <div class="course-card">
                    <?php if ($enrollment['thumbnail']): ?>
                        <img src="<?= url($enrollment['thumbnail']) ?>" alt="<?= e($enrollment['title']) ?>" class="course-thumbnail">
                    <?php else: ?>
                        <div class="course-thumbnail"></div>
                    <?php endif; ?>

                    <div class="course-content">
                        <h3 class="course-title"><?= e($enrollment['title']) ?></h3>
                        <p class="course-meta">Instructor: <?= e($enrollment['instructor_name']) ?></p>

                        <div class="progress">
                            <div class="progress-bar" style="width: <?= $enrollment['progress_percent'] ?>%">
                                <?= round($enrollment['progress_percent']) ?>%
                            </div>
                        </div>

                        <div class="mt-2">
                            <span class="badge badge-<?= $enrollment['status'] === 'completed' ? 'success' : 'primary' ?>">
                                <?= ucfirst($enrollment['status']) ?>
                            </span>
                            <span class="badge badge-secondary"><?= e($enrollment['level']) ?></span>
                        </div>

                        <a href="<?= url('learn/' . $enrollment['slug']) ?>" class="btn btn-primary btn-sm mt-2" style="width: 100%;">
                            <?= $enrollment['progress_percent'] > 0 ? 'Continue Learning' : 'Start Course' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($certificates)): ?>
    <div class="card mt-4">
        <h2 class="card-title">My Certificates</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Certificate Number</th>
                    <th>Issued Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certificates as $cert): ?>
                    <tr>
                        <td><?= e($cert['course_title']) ?></td>
                        <td><?= e($cert['certificate_number']) ?></td>
                        <td><?= formatDate($cert['issued_at']) ?></td>
                        <td>
                            <a href="<?= url('certificate/' . $cert['id']) ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
