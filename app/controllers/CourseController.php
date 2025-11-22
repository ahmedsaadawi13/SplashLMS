<?php
// FILE: /app/controllers/CourseController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * CourseController - Course management for admins/instructors
 */
class CourseController extends Controller
{
    /**
     * List courses
     */
    public function index()
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        $user = $this->getUser();
        $courseModel = $this->model('Course');

        $page = max(1, (int)$this->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        if ($user['role'] === 'instructor') {
            $courses = $courseModel->getCoursesByInstructor($user['id'], $user['tenant_id']);
            $total = count($courses);
            $courses = array_slice($courses, $offset, $perPage);
        } else {
            $courses = $courseModel->getCoursesByTenant($user['tenant_id'], $perPage, $offset);
            $total = $courseModel->count(['tenant_id' => $user['tenant_id']]);
        }

        $pagination = paginate($total, $page, $perPage);

        $this->view('admin/courses/index', [
            'courses' => $courses,
            'pagination' => $pagination
        ]);
    }

    /**
     * Show create course form
     */
    public function create()
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        $user = $this->getUser();

        // Check subscription limits
        $subscriptionModel = $this->model('TenantSubscription');
        $tenantUsageModel = $this->model('TenantUsage');

        $subscription = $subscriptionModel->getActiveSubscription($user['tenant_id']);

        if (!$subscription || !$tenantUsageModel->canCreateCourse($user['tenant_id'], $subscription['max_courses'])) {
            $this->flash('error', 'Course creation limit reached. Please upgrade your subscription.');
            $this->redirect('admin/courses');
        }

        $categoryModel = $this->model('Category');
        $categories = $categoryModel->getCategoriesByTenant($user['tenant_id']);

        $this->view('admin/courses/create', [
            'categories' => $categories,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Store new course
     */
    public function store()
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('admin/courses/create');
        }

        $user = $this->getUser();
        $courseModel = $this->model('Course');
        $tenantUsageModel = $this->model('TenantUsage');

        $data = [
            'tenant_id' => $user['tenant_id'],
            'instructor_id' => $user['id'],
            'category_id' => $this->input('category_id') ?: null,
            'title' => $this->sanitize($this->input('title')),
            'short_description' => $this->sanitize($this->input('short_description')),
            'description' => $this->input('description'),
            'level' => $this->input('level', 'beginner'),
            'language' => $this->input('language', 'en'),
            'price' => (float)$this->input('price', 0),
            'currency' => $this->input('currency', 'USD'),
            'status' => $this->input('status', 'draft'),
            'visibility' => $this->input('visibility', 'public'),
            'certificate_enabled' => $this->input('certificate_enabled') ? 1 : 0
        ];

        $courseId = $courseModel->createCourse($data);

        // Update usage
        $tenantUsageModel->recalculateUsage($user['tenant_id']);

        $this->flash('success', 'Course created successfully.');
        $this->redirect('admin/courses/' . $courseId . '/edit');
    }

    /**
     * Show edit course form
     *
     * @param int $id Course ID
     */
    public function edit($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        $user = $this->getUser();
        $courseModel = $this->model('Course');
        $categoryModel = $this->model('Category');
        $sectionModel = $this->model('CourseSection');
        $quizModel = $this->model('Quiz');

        $course = $courseModel->find($id);

        if (!$course || $course['tenant_id'] != $user['tenant_id']) {
            $this->flash('error', 'Course not found.');
            $this->redirect('admin/courses');
        }

        // Check instructor permission
        if ($user['role'] === 'instructor' && $course['instructor_id'] != $user['id']) {
            $this->flash('error', 'You do not have permission to edit this course.');
            $this->redirect('admin/courses');
        }

        $categories = $categoryModel->getCategoriesByTenant($user['tenant_id']);
        $sections = $sectionModel->getSectionsWithLessons($id);
        $quizzes = $quizModel->getQuizzesByCourse($id);

        $this->view('admin/courses/edit', [
            'course' => $course,
            'categories' => $categories,
            'sections' => $sections,
            'quizzes' => $quizzes,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Update course
     *
     * @param int $id Course ID
     */
    public function update($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('admin/courses/' . $id . '/edit');
        }

        $user = $this->getUser();
        $courseModel = $this->model('Course');

        $course = $courseModel->find($id);

        if (!$course || $course['tenant_id'] != $user['tenant_id']) {
            $this->flash('error', 'Course not found.');
            $this->redirect('admin/courses');
        }

        if ($user['role'] === 'instructor' && $course['instructor_id'] != $user['id']) {
            $this->flash('error', 'You do not have permission to edit this course.');
            $this->redirect('admin/courses');
        }

        $data = [
            'category_id' => $this->input('category_id') ?: null,
            'title' => $this->sanitize($this->input('title')),
            'short_description' => $this->sanitize($this->input('short_description')),
            'description' => $this->input('description'),
            'level' => $this->input('level'),
            'price' => (float)$this->input('price'),
            'status' => $this->input('status'),
            'visibility' => $this->input('visibility'),
            'certificate_enabled' => $this->input('certificate_enabled') ? 1 : 0
        ];

        // Set published_at if publishing for first time
        if ($data['status'] === 'published' && !$course['published_at']) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $courseModel->update($id, $data);

        $this->flash('success', 'Course updated successfully.');
        $this->redirect('admin/courses/' . $id . '/edit');
    }

    /**
     * Delete course
     *
     * @param int $id Course ID
     */
    public function delete($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('admin/courses');
        }

        $user = $this->getUser();
        $courseModel = $this->model('Course');

        $course = $courseModel->find($id);

        if (!$course || $course['tenant_id'] != $user['tenant_id']) {
            $this->flash('error', 'Course not found.');
            $this->redirect('admin/courses');
        }

        if ($user['role'] === 'instructor' && $course['instructor_id'] != $user['id']) {
            $this->flash('error', 'You do not have permission to delete this course.');
            $this->redirect('admin/courses');
        }

        $courseModel->delete($id);

        // Update usage
        $tenantUsageModel = $this->model('TenantUsage');
        $tenantUsageModel->recalculateUsage($user['tenant_id']);

        $this->flash('success', 'Course deleted successfully.');
        $this->redirect('admin/courses');
    }
}
