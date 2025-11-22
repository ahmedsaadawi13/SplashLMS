<?php
// FILE: /app/controllers/ApiController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * ApiController - Public API endpoints
 */
class ApiController extends Controller
{
    private $tenant;

    public function __construct()
    {
        parent::__construct();
        $this->authenticateApi();
    }

    /**
     * Authenticate API request using X-API-KEY header
     */
    private function authenticateApi()
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if (empty($apiKey)) {
            $this->jsonError('API key required', 401);
        }

        $tenantModel = $this->model('Tenant');
        $tenant = $tenantModel->findByApiKey($apiKey);

        if (!$tenant || $tenant['status'] !== 'active') {
            $this->jsonError('Invalid API key', 401);
        }

        $this->tenant = $tenant;
    }

    /**
     * Return JSON error
     *
     * @param string $message
     * @param int $code
     */
    private function jsonError($message, $code = 400)
    {
        $this->json([
            'success' => false,
            'error' => $message
        ], $code);
    }

    /**
     * GET /api/catalog - List published courses
     */
    public function catalog()
    {
        $courseModel = $this->model('Course');

        // Get filters from query params
        $filters = [
            'category_id' => $this->input('category_id'),
            'level' => $this->input('level'),
            'search' => $this->input('search')
        ];

        // Pagination
        $page = max(1, (int)$this->input('page', 1));
        $perPage = min(100, max(1, (int)$this->input('per_page', 20)));

        $total = $courseModel->countPublishedCourses($this->tenant['id'], $filters);
        $pagination = paginate($total, $page, $perPage);

        $courses = $courseModel->getPublishedCourses(
            $this->tenant['id'],
            $filters,
            $perPage,
            $pagination['offset']
        );

        // Format response
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course['id'],
                'title' => $course['title'],
                'slug' => $course['slug'],
                'short_description' => $course['short_description'],
                'category' => $course['category_name'],
                'level' => $course['level'],
                'language' => $course['language'],
                'price' => (float)$course['price'],
                'currency' => $course['currency'],
                'thumbnail' => $course['thumbnail'],
                'instructor_name' => $course['instructor_name'],
                'enrollments_count' => (int)$course['enrollments_count'],
                'published_at' => $course['published_at']
            ];
        }

        $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $pagination['total'],
                'per_page' => $pagination['per_page'],
                'current_page' => $pagination['current_page'],
                'total_pages' => $pagination['total_pages']
            ]
        ]);
    }

    /**
     * GET /api/course/{id} - Get course details
     *
     * @param int $id Course ID
     */
    public function courseDetail($id)
    {
        $courseModel = $this->model('Course');
        $sectionModel = $this->model('CourseSection');

        $course = $courseModel->getCourseWithDetails($id);

        if (!$course || $course['tenant_id'] != $this->tenant['id'] || $course['status'] !== 'published') {
            $this->jsonError('Course not found', 404);
        }

        // Get course structure
        $sections = $sectionModel->getSectionsWithLessons($id);

        // Format sections and lessons
        $sectionsData = [];
        foreach ($sections as $section) {
            $lessonsData = [];
            foreach ($section['lessons'] as $lesson) {
                $lessonsData[] = [
                    'id' => $lesson['id'],
                    'title' => $lesson['title'],
                    'type' => $lesson['type'],
                    'duration_minutes' => (int)$lesson['duration_minutes'],
                    'is_preview' => (bool)$lesson['is_preview']
                ];
            }

            $sectionsData[] = [
                'id' => $section['id'],
                'title' => $section['title'],
                'description' => $section['description'],
                'lessons' => $lessonsData
            ];
        }

        $this->json([
            'success' => true,
            'data' => [
                'id' => $course['id'],
                'title' => $course['title'],
                'slug' => $course['slug'],
                'short_description' => $course['short_description'],
                'description' => $course['description'],
                'category' => $course['category_name'],
                'level' => $course['level'],
                'language' => $course['language'],
                'price' => (float)$course['price'],
                'currency' => $course['currency'],
                'thumbnail' => $course['thumbnail'],
                'instructor' => [
                    'name' => $course['instructor_name'],
                    'bio' => $course['instructor_bio']
                ],
                'enrollments_count' => (int)$course['enrollments_count'],
                'lessons_count' => (int)$course['lessons_count'],
                'sections' => $sectionsData,
                'published_at' => $course['published_at']
            ]
        ]);
    }

    /**
     * POST /api/enroll - Enroll a student in a course
     */
    public function enroll()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }

        // Get JSON body
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->jsonError('Invalid JSON');
        }

        $courseId = $input['course_id'] ?? null;
        $studentEmail = $input['student_email'] ?? null;
        $studentName = $input['student_name'] ?? null;

        if (!$courseId) {
            $this->jsonError('course_id is required');
        }

        // Verify course exists and belongs to tenant
        $courseModel = $this->model('Course');
        $course = $courseModel->find($courseId);

        if (!$course || $course['tenant_id'] != $this->tenant['id']) {
            $this->jsonError('Course not found', 404);
        }

        if ($course['status'] !== 'published') {
            $this->jsonError('Course is not available for enrollment');
        }

        // Find or create student
        $userModel = $this->model('User');
        $student = $userModel->findByEmail($studentEmail, $this->tenant['id']);

        if (!$student) {
            // Create new student
            if (empty($studentName) || empty($studentEmail)) {
                $this->jsonError('student_name and student_email are required for new students');
            }

            // Check subscription limits
            $subscriptionModel = $this->model('TenantSubscription');
            $tenantUsageModel = $this->model('TenantUsage');

            $subscription = $subscriptionModel->getActiveSubscription($this->tenant['id']);

            if (!$subscription || !$tenantUsageModel->canAddStudent($this->tenant['id'], $subscription['max_students'])) {
                $this->jsonError('Student limit reached');
            }

            $studentId = $userModel->createUser([
                'tenant_id' => $this->tenant['id'],
                'email' => $studentEmail,
                'password' => bin2hex(random_bytes(8)), // Random password
                'name' => $studentName,
                'role' => 'student'
            ]);

            $tenantUsageModel->recalculateUsage($this->tenant['id']);
        } else {
            $studentId = $student['id'];
        }

        // Check if already enrolled
        $enrollmentModel = $this->model('Enrollment');

        if ($enrollmentModel->isEnrolled($studentId, $courseId)) {
            $this->jsonError('Student is already enrolled in this course');
        }

        // Check enrollment limits
        $subscriptionModel = $this->model('TenantSubscription');
        $tenantUsageModel = $this->model('TenantUsage');

        $subscription = $subscriptionModel->getActiveSubscription($this->tenant['id']);

        if (!$subscription || !$tenantUsageModel->canCreateEnrollment($this->tenant['id'], $subscription['max_enrollments'])) {
            $this->jsonError('Enrollment limit reached');
        }

        // Create enrollment
        $enrollmentId = $enrollmentModel->enrollStudent([
            'tenant_id' => $this->tenant['id'],
            'student_id' => $studentId,
            'course_id' => $courseId,
            'enrollment_type' => 'free'
        ]);

        $tenantUsageModel->recalculateUsage($this->tenant['id']);

        // Send notification
        $notificationModel = $this->model('Notification');
        $notificationModel->sendEnrollmentConfirmation(
            $this->tenant['id'],
            $studentId,
            $studentName ?? $student['name'],
            $course['title']
        );

        $this->json([
            'success' => true,
            'data' => [
                'enrollment_id' => $enrollmentId,
                'course_id' => $courseId,
                'student_id' => $studentId,
                'status' => 'active'
            ]
        ], 201);
    }
}
