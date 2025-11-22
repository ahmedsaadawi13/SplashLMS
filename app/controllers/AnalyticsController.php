<?php
// FILE: /app/controllers/AnalyticsController.php

require_once __DIR__ . '/../core/Controller.php';

class AnalyticsController extends Controller
{
    public function index()
    {
        $this->requireRole(['tenant_admin', 'instructor']);
        $user = $this->getUser();

        $courseModel = $this->model('Course');
        $enrollmentModel = $this->model('Enrollment');
        $userModel = $this->model('User');

        $totalCourses = $courseModel->count(['tenant_id' => $user['tenant_id']]);
        $publishedCourses = $courseModel->count(['tenant_id' => $user['tenant_id'], 'status' => 'published']);
        $totalStudents = $userModel->countByTenant($user['tenant_id'], 'student');
        $totalEnrollments = $enrollmentModel->countByTenant($user['tenant_id']);
        $revenue = $enrollmentModel->getTotalRevenue($user['tenant_id']);

        // Get popular courses
        $sql = "SELECT c.id, c.title, COUNT(e.id) as enrollments_count
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                WHERE c.tenant_id = :tenant_id
                GROUP BY c.id
                ORDER BY enrollments_count DESC
                LIMIT 10";

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute(['tenant_id' => $user['tenant_id']]);
        $popularCourses = $stmt->fetchAll();

        $this->view('admin/analytics/index', [
            'totalCourses' => $totalCourses,
            'publishedCourses' => $publishedCourses,
            'totalStudents' => $totalStudents,
            'totalEnrollments' => $totalEnrollments,
            'revenue' => $revenue,
            'popularCourses' => $popularCourses
        ]);
    }

    public function course($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);
        $user = $this->getUser();

        $courseModel = $this->model('Course');
        $enrollmentModel = $this->model('Enrollment');

        $course = $courseModel->getCourseWithDetails($id);

        if (!$course || $course['tenant_id'] != $user['tenant_id']) {
            $this->flash('error', 'Course not found.');
            $this->redirect('admin/analytics');
        }

        $totalEnrollments = $enrollmentModel->count(['course_id' => $id]);
        $completedEnrollments = $enrollmentModel->countCompletedEnrollments($id);
        $completionRate = $totalEnrollments > 0 ? ($completedEnrollments / $totalEnrollments) * 100 : 0;

        $this->view('admin/analytics/course', [
            'course' => $course,
            'totalEnrollments' => $totalEnrollments,
            'completedEnrollments' => $completedEnrollments,
            'completionRate' => round($completionRate, 2)
        ]);
    }
}
