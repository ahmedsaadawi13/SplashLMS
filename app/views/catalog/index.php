<?php
// FILE: /app/views/catalog/index.php
$pageTitle = 'Browse Courses';
ob_start();
?>

<h1>Browse Courses</h1>

<div class="card mt-3">
    <form method="GET" action="<?= url('catalog') ?>">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?= e($filters['search'] ?? '') ?>">
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?> (<?= $category['courses_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">Level</label>
                <select name="level" class="form-control">
                    <option value="">All Levels</option>
                    <option value="beginner" <?= ($filters['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= ($filters['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= ($filters['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
</div>

<?php if (empty($courses)): ?>
    <div class="card mt-3">
        <p>No courses found.</p>
    </div>
<?php else: ?>
    <div class="grid mt-3">
        <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <?php if ($course['thumbnail']): ?>
                    <img src="<?= url($course['thumbnail']) ?>" alt="<?= e($course['title']) ?>" class="course-thumbnail">
                <?php else: ?>
                    <div class="course-thumbnail"></div>
                <?php endif; ?>

                <div class="course-content">
                    <h3 class="course-title"><?= e($course['title']) ?></h3>
                    <p class="course-meta">
                        <?= e($course['instructor_name']) ?> •
                        <span class="badge badge-secondary"><?= e($course['level']) ?></span>
                    </p>

                    <p><?= truncate($course['short_description'], 100) ?></p>

                    <div class="d-flex justify-between align-center mt-2">
                        <strong style="font-size: 1.25rem; color: #007bff;">
                            <?= $course['price'] > 0 ? formatCurrency($course['price'], $course['currency']) : 'Free' ?>
                        </strong>
                        <a href="<?= url('course/' . $course['slug']) ?>" class="btn btn-primary btn-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination mt-4">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="<?= url('catalog?page=' . $i) ?>" class="<?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
