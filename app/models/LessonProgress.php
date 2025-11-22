<?php
// FILE: /app/models/LessonProgress.php

require_once __DIR__ . '/../core/Model.php';

/**
 * LessonProgress Model
 */
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    /**
     * Get progress by enrollment
     *
     * @param int $enrollmentId
     * @return array
     */
    public function getProgressByEnrollment($enrollmentId)
    {
        return $this->findAll(['enrollment_id' => $enrollmentId], 'last_accessed_at DESC');
    }

    /**
     * Get or create lesson progress
     *
     * @param int $enrollmentId
     * @param int $lessonId
     * @return array
     */
    public function getOrCreate($enrollmentId, $lessonId)
    {
        $progress = $this->findOne([
            'enrollment_id' => $enrollmentId,
            'lesson_id' => $lessonId
        ]);

        if (!$progress) {
            $progressId = $this->insert([
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
                'status' => 'not_started'
            ]);

            $progress = $this->find($progressId);
        }

        return $progress;
    }

    /**
     * Mark lesson as completed
     *
     * @param int $enrollmentId
     * @param int $lessonId
     * @return bool
     */
    public function markCompleted($enrollmentId, $lessonId)
    {
        $progress = $this->getOrCreate($enrollmentId, $lessonId);

        if ($progress['status'] === 'completed') {
            return true; // Already completed
        }

        $updated = $this->update($progress['id'], [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'last_accessed_at' => date('Y-m-d H:i:s')
        ]);

        if ($updated) {
            // Update enrollment progress
            $this->updateEnrollmentProgress($enrollmentId);
        }

        return $updated;
    }

    /**
     * Update enrollment progress
     *
     * @param int $enrollmentId
     */
    private function updateEnrollmentProgress($enrollmentId)
    {
        require_once __DIR__ . '/Enrollment.php';
        $enrollmentModel = new Enrollment();
        $enrollment = $enrollmentModel->find($enrollmentId);

        if (!$enrollment) {
            return;
        }

        // Count completed lessons
        $completedCount = $this->count([
            'enrollment_id' => $enrollmentId,
            'status' => 'completed'
        ]);

        $enrollmentModel->updateProgress(
            $enrollmentId,
            $completedCount,
            $enrollment['total_lessons']
        );
    }

    /**
     * Get completed lessons count
     *
     * @param int $enrollmentId
     * @return int
     */
    public function getCompletedCount($enrollmentId)
    {
        return $this->count([
            'enrollment_id' => $enrollmentId,
            'status' => 'completed'
        ]);
    }
}
