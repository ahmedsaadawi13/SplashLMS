<?php
// FILE: /app/controllers/CatalogController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * CatalogController - Public course catalog
 */
class CatalogController extends Controller
{
    /**
     * Show course catalog
     */
    public function index()
    {
        $this->requireAuth();

        $user = $this->getUser();
        $courseModel = $this->model('Course');
        $categoryModel = $this->model('Category');

        // Get filters
        $filters = [
            'category_id' => $this->input('category'),
            'level' => $this->input('level'),
            'search' => $this->input('search'),
        ];

        if ($this->input('free') !== null) {
            $filters['is_free'] = $this->input('free') == '1';
        }

        // Pagination
        $page = max(1, (int)$this->input('page', 1));
        $perPage = 12;

        $total = $courseModel->countPublishedCourses($user['tenant_id'], $filters);
        $pagination = paginate($total, $page, $perPage);

        $courses = $courseModel->getPublishedCourses(
            $user['tenant_id'],
            $filters,
            $perPage,
            $pagination['offset']
        );

        $categories = $categoryModel->getCategoriesWithCourseCount($user['tenant_id']);

        $this->view('catalog/index', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => $filters,
            'pagination' => $pagination
        ]);
    }

    /**
     * Show course details
     *
     * @param string $slug Course slug
     */
    public function course($slug)
    {
        $this->requireAuth();

        $user = $this->getUser();
        $courseModel = $this->model('Course');
        $sectionModel = $this->model('CourseSection');
        $lessonModel = $this->model('Lesson');
        $enrollmentModel = $this->model('Enrollment');
        $quizModel = $this->model('Quiz');

        $course = $courseModel->findBySlug($slug, $user['tenant_id']);

        if (!$course || $course['status'] !== 'published') {
            $this->flash('error', 'Course not found.');
            $this->redirect('catalog');
        }

        // Get course details
        $courseDetails = $courseModel->getCourseWithDetails($course['id']);

        // Get course structure
        $sections = $sectionModel->getSectionsWithLessons($course['id']);

        // Get preview lessons
        $previewLessons = $lessonModel->getPreviewLessons($course['id']);

        // Get quizzes
        $quizzes = $quizModel->getQuizzesByCourse($course['id']);

        // Check if enrolled
        $isEnrolled = $enrollmentModel->isEnrolled($user['id'], $course['id']);

        $this->view('catalog/course', [
            'course' => $courseDetails,
            'sections' => $sections,
            'previewLessons' => $previewLessons,
            'quizzes' => $quizzes,
            'isEnrolled' => $isEnrolled,
            'csrf_token' => $this->generateCSRF()
        ]);
    }
}
