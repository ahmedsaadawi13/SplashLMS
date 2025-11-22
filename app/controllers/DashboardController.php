<?php
// FILE: /app/controllers/DashboardController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * DashboardController - Main dashboard for all users
 */
class DashboardController extends Controller
{
    /**
     * Show dashboard based on role
     */
    public function index()
    {
        $this->requireAuth();

        $user = $this->getUser();

        switch ($user['role']) {
            case 'platform_admin':
                $this->redirect('platform/dashboard');
                break;

            case 'tenant_admin':
            case 'instructor':
                $this->adminDashboard();
                break;

            case 'student':
                $this->studentDashboard();
                break;

            default:
                $this->redirect('login');
        }
    }

    /**
     * Admin/Instructor dashboard
     */
    private function adminDashboard()
    {
        $user = $this->getUser();
        $tenantId = $user['tenant_id'];

        $courseModel = $this->model('Course');
        $userModel = $this->model('User');
        $enrollmentModel = $this->model('Enrollment');
        $tenantModel = $this->model('Tenant');

        // Get stats
        if ($user['role'] === 'tenant_admin') {
            $totalCourses = $courseModel->count(['tenant_id' => $tenantId]);
            $courses = $courseModel->getCoursesByTenant($tenantId, 5);
        } else {
            // Instructor - only their courses
            $totalCourses = $courseModel->count([
                'tenant_id' => $tenantId,
                'instructor_id' => $user['id']
            ]);
            $courses = $courseModel->getCoursesByInstructor($user['id'], $tenantId);
            $courses = array_slice($courses, 0, 5);
        }

        $totalStudents = $userModel->countByTenant($tenantId, 'student');
        $totalEnrollments = $enrollmentModel->countByTenant($tenantId);
        $revenue = $enrollmentModel->getTotalRevenue($tenantId);

        // Get tenant with subscription
        $tenant = $tenantModel->getWithSubscription($tenantId);

        // Recent enrollments
        $recentEnrollments = $enrollmentModel->getEnrollmentsByTenant($tenantId, 10);

        $this->view('dashboard/admin', [
            'user' => $user,
            'tenant' => $tenant,
            'totalCourses' => $totalCourses,
            'totalStudents' => $totalStudents,
            'totalEnrollments' => $totalEnrollments,
            'revenue' => $revenue,
            'courses' => $courses,
            'recentEnrollments' => $recentEnrollments
        ]);
    }

    /**
     * Student dashboard
     */
    private function studentDashboard()
    {
        $user = $this->getUser();

        $enrollmentModel = $this->model('Enrollment');
        $certificateModel = $this->model('Certificate');

        $enrollments = $enrollmentModel->getStudentEnrollments($user['id']);
        $certificates = $certificateModel->getStudentCertificates($user['id']);

        // Calculate overall progress
        $totalProgress = 0;
        $completedCourses = 0;

        foreach ($enrollments as $enrollment) {
            $totalProgress += $enrollment['progress_percent'];
            if ($enrollment['status'] === 'completed') {
                $completedCourses++;
            }
        }

        $avgProgress = count($enrollments) > 0 ? $totalProgress / count($enrollments) : 0;

        $this->view('dashboard/student', [
            'user' => $user,
            'enrollments' => $enrollments,
            'certificates' => $certificates,
            'totalEnrollments' => count($enrollments),
            'completedCourses' => $completedCourses,
            'avgProgress' => round($avgProgress, 2)
        ]);
    }
}
