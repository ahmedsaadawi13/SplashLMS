<?php
// FILE: /app/models/Enrollment.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Enrollment Model
 */
class Enrollment extends Model
{
    protected $table = 'enrollments';

    /**
     * Check if student is enrolled in course
     *
     * @param int $studentId
     * @param int $courseId
     * @return bool
     */
    public function isEnrolled($studentId, $courseId)
    {
        $enrollment = $this->findOne([
            'student_id' => $studentId,
            'course_id' => $courseId
        ]);

        return $enrollment !== null;
    }

    /**
     * Get enrollment by student and course
     *
     * @param int $studentId
     * @param int $courseId
     * @return array|null
     */
    public function getEnrollment($studentId, $courseId)
    {
        return $this->findOne([
            'student_id' => $studentId,
            'course_id' => $courseId
        ]);
    }

    /**
     * Get student enrollments with course details
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentEnrollments($studentId)
    {
        $sql = "SELECT e.*, c.title, c.slug, c.thumbnail, c.level,
                       u.name as instructor_name
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE e.student_id = :student_id
                ORDER BY e.last_accessed_at DESC, e.enrolled_at DESC";

        return $this->query($sql, ['student_id' => $studentId]);
    }

    /**
     * Get course enrollments
     *
     * @param int $courseId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCourseEnrollments($courseId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT e.*, u.name, u.email
                FROM enrollments e
                JOIN users u ON e.student_id = u.id
                WHERE e.course_id = :course_id
                ORDER BY e.enrolled_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->query($sql, ['course_id' => $courseId]);
    }

    /**
     * Enroll student in course
     *
     * @param array $data
     * @return int
     */
    public function enrollStudent($data)
    {
        // Set defaults
        $data['status'] = $data['status'] ?? 'active';
        $data['progress_percent'] = 0;
        $data['completed_lessons'] = 0;

        // Get total lessons for the course
        require_once __DIR__ . '/Lesson.php';
        $lessonModel = new Lesson();
        $data['total_lessons'] = $lessonModel->countLessonsByCourse($data['course_id']);

        return $this->insert($data);
    }

    /**
     * Update enrollment progress
     *
     * @param int $enrollmentId
     * @param int $completedLessons
     * @param int $totalLessons
     */
    public function updateProgress($enrollmentId, $completedLessons, $totalLessons)
    {
        $progressPercent = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        $data = [
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'progress_percent' => round($progressPercent, 2),
            'last_accessed_at' => date('Y-m-d H:i:s')
        ];

        // Mark as completed if all lessons done
        if ($completedLessons >= $totalLessons && $totalLessons > 0) {
            $data['status'] = 'completed';
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->update($enrollmentId, $data);
    }

    /**
     * Get enrollments by tenant
     *
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getEnrollmentsByTenant($tenantId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT e.*, c.title as course_title, u.name as student_name, u.email
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN users u ON e.student_id = u.id
                WHERE e.tenant_id = :tenant_id
                ORDER BY e.enrolled_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->query($sql, ['tenant_id' => $tenantId]);
    }

    /**
     * Count enrollments by tenant
     *
     * @param int $tenantId
     * @return int
     */
    public function countByTenant($tenantId)
    {
        return $this->count(['tenant_id' => $tenantId]);
    }

    /**
     * Get completed enrollments count by course
     *
     * @param int $courseId
     * @return int
     */
    public function countCompletedEnrollments($courseId)
    {
        return $this->count(['course_id' => $courseId, 'status' => 'completed']);
    }

    /**
     * Get revenue from paid enrollments
     *
     * @param int $tenantId
     * @return float
     */
    public function getTotalRevenue($tenantId)
    {
        $sql = "SELECT SUM(p.amount) as total_revenue
                FROM enrollments e
                JOIN payments p ON e.payment_id = p.id
                WHERE e.tenant_id = :tenant_id
                AND e.enrollment_type = 'paid'
                AND p.status = 'completed'";

        $result = $this->queryOne($sql, ['tenant_id' => $tenantId]);
        return (float)($result['total_revenue'] ?? 0);
    }
}
