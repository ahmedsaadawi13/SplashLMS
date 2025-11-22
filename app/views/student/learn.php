<?php
// FILE: /app/views/student/learn.php
$pageTitle = $course['title'] . ' - Learn';
ob_start();
?>

<style>
.learning-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    margin-top: 1rem;
}
.course-sidebar {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    height: fit-content;
}
.lesson-list {
    list-style: none;
}
.lesson-item {
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    border: 1px solid #ddd;
}
.lesson-item.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}
.lesson-item.completed {
    background: #28a745;
    color: #fff;
    border-color: #28a745;
}
.lesson-content {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
}
</style>

<div class="card">
    <h1><?= e($course['title']) ?></h1>
    <div class="progress mt-2">
        <div class="progress-bar" style="width: <?= $enrollment['progress_percent'] ?>%">
            <?= round($enrollment['progress_percent']) ?>%
        </div>
    </div>
</div>

<div class="learning-container">
    <aside class="course-sidebar">
        <h3>Course Content</h3>

        <?php foreach ($sections as $section): ?>
            <div class="mt-3">
                <h4><?= e($section['title']) ?></h4>
                <ul class="lesson-list">
                    <?php foreach ($section['lessons'] as $lesson): ?>
                        <?php
                        $isCompleted = isset($progressMap[$lesson['id']]) && $progressMap[$lesson['id']]['status'] === 'completed';
                        $isActive = $currentLesson && $currentLesson['id'] == $lesson['id'];
                        $classes = 'lesson-item';
                        if ($isActive) $classes .= ' active';
                        elseif ($isCompleted) $classes .= ' completed';
                        ?>
                        <li class="<?= $classes ?>" data-lesson-id="<?= $lesson['id'] ?>">
                            <a href="<?= url('learn/' . $course['slug'] . '?lesson=' . $lesson['id']) ?>" style="color: inherit; text-decoration: none;">
                                <?= $isCompleted ? '✓ ' : '' ?><?= e($lesson['title']) ?>
                                <small style="display: block;"><?= $lesson['duration_minutes'] ?> min</small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($quizzes)): ?>
            <div class="mt-3">
                <h4>Quizzes</h4>
                <ul class="lesson-list">
                    <?php foreach ($quizzes as $quiz): ?>
                        <li class="lesson-item">
                            <a href="<?= url('quiz/' . $quiz['id']) ?>" style="color: inherit; text-decoration: none;">
                                📝 <?= e($quiz['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </aside>

    <div class="lesson-content">
        <?php if ($currentLesson): ?>
            <h2><?= e($currentLesson['title']) ?></h2>

            <?php if ($currentLesson['type'] === 'video' && $currentLesson['video_url']): ?>
                <div class="video-container mt-3">
                    <?php
                    $videoUrl = $currentLesson['video_url'];
                    if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false):
                        // Extract YouTube ID
                        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?]+)/', $videoUrl, $matches);
                        $videoId = $matches[1] ?? '';
                        if ($videoId):
                    ?>
                        <iframe src="https://www.youtube.com/embed/<?= e($videoId) ?>" frameborder="0" allowfullscreen></iframe>
                    <?php endif; endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentLesson['type'] === 'text' && $currentLesson['content']): ?>
                <div class="mt-3">
                    <?= $currentLesson['content'] ?>
                </div>
            <?php endif; ?>

            <?php if ($currentLesson['attachment']): ?>
                <div class="mt-3">
                    <a href="<?= url($currentLesson['attachment']) ?>" class="btn btn-secondary" download>
                        Download Attachment
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <?php
                $isCompleted = isset($progressMap[$currentLesson['id']]) && $progressMap[$currentLesson['id']]['status'] === 'completed';
                ?>
                <?php if (!$isCompleted): ?>
                    <button onclick="markLessonComplete(<?= $currentLesson['id'] ?>, '<?= e($csrf_token) ?>')" class="btn btn-success">
                        Mark as Complete
                    </button>
                <?php else: ?>
                    <span class="badge badge-success">✓ Completed</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Select a lesson from the sidebar to start learning.</p>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
