<?php
// FILE: /app/controllers/StudentController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * StudentController - Handles student learning experience
 */
class StudentController extends Controller
{
    /**
     * Show student's enrolled courses
     */
    public function myCourses()
    {
        $this->requireRole('student');

        $user = $this->getUser();
        $enrollmentModel = $this->model('Enrollment');

        $enrollments = $enrollmentModel->getStudentEnrollments($user['id']);

        $this->view('student/my-courses', [
            'enrollments' => $enrollments
        ]);
    }

    /**
     * Course learning interface
     *
     * @param string $slug Course slug
     */
    public function learn($slug)
    {
        $this->requireRole('student');

        $user = $this->getUser();
        $courseModel = $this->model('Course');
        $enrollmentModel = $this->model('Enrollment');
        $sectionModel = $this->model('CourseSection');
        $lessonProgressModel = $this->model('LessonProgress');
        $quizModel = $this->model('Quiz');

        // Get course
        $course = $courseModel->findBySlug($slug, $user['tenant_id']);

        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('my-courses');
        }

        // Check enrollment
        $enrollment = $enrollmentModel->getEnrollment($user['id'], $course['id']);

        if (!$enrollment) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('catalog');
        }

        // Get course structure with lessons
        $sections = $sectionModel->getSectionsWithLessons($course['id']);

        // Get lesson progress
        $progressList = $lessonProgressModel->getProgressByEnrollment($enrollment['id']);
        $progressMap = [];
        foreach ($progressList as $progress) {
            $progressMap[$progress['lesson_id']] = $progress;
        }

        // Get quizzes
        $quizzes = $quizModel->getQuizzesByCourse($course['id']);

        // Get current lesson (from query param or first incomplete lesson)
        $currentLessonId = $this->input('lesson');

        if (!$currentLessonId) {
            // Find first incomplete lesson
            foreach ($sections as $section) {
                foreach ($section['lessons'] as $lesson) {
                    if (!isset($progressMap[$lesson['id']]) || $progressMap[$lesson['id']]['status'] !== 'completed') {
                        $currentLessonId = $lesson['id'];
                        break 2;
                    }
                }
            }

            // If all complete, show first lesson
            if (!$currentLessonId && !empty($sections[0]['lessons'])) {
                $currentLessonId = $sections[0]['lessons'][0]['id'];
            }
        }

        // Get current lesson details
        $lessonModel = $this->model('Lesson');
        $currentLesson = $lessonModel->find($currentLessonId);

        $this->view('student/learn', [
            'course' => $course,
            'enrollment' => $enrollment,
            'sections' => $sections,
            'currentLesson' => $currentLesson,
            'progressMap' => $progressMap,
            'quizzes' => $quizzes,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Enroll in a course
     *
     * @param int $id Course ID
     */
    public function enroll($id)
    {
        $this->requireRole('student');

        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('catalog');
        }

        $user = $this->getUser();
        $courseModel = $this->model('Course');
        $enrollmentModel = $this->model('Enrollment');
        $tenantUsageModel = $this->model('TenantUsage');
        $subscriptionModel = $this->model('TenantSubscription');
        $notificationModel = $this->model('Notification');

        $course = $courseModel->find($id);

        if (!$course || $course['tenant_id'] != $user['tenant_id']) {
            $this->flash('error', 'Course not found.');
            $this->redirect('catalog');
        }

        // Check if already enrolled
        if ($enrollmentModel->isEnrolled($user['id'], $id)) {
            $this->flash('info', 'You are already enrolled in this course.');
            $this->redirect('learn/' . $course['slug']);
        }

        // Check subscription limits
        $subscription = $subscriptionModel->getActiveSubscription($user['tenant_id']);
        if (!$subscription || !$tenantUsageModel->canCreateEnrollment($user['tenant_id'], $subscription['max_enrollments'])) {
            $this->flash('error', 'Enrollment limit reached. Please upgrade your subscription.');
            $this->redirect('catalog');
        }

        // Handle paid courses
        $paymentId = null;
        if ($course['price'] > 0) {
            // Simulate payment
            $paymentModel = $this->model('Payment');
            $paymentId = $paymentModel->createPayment([
                'tenant_id' => $user['tenant_id'],
                'amount' => $course['price'],
                'currency' => $course['currency'],
                'payment_method' => 'simulated',
                'status' => 'completed'
            ]);
        }

        // Create enrollment
        $enrollmentModel->enrollStudent([
            'tenant_id' => $user['tenant_id'],
            'student_id' => $user['id'],
            'course_id' => $id,
            'enrollment_type' => $course['price'] > 0 ? 'paid' : 'free',
            'payment_id' => $paymentId
        ]);

        // Update usage
        $tenantUsageModel->recalculateUsage($user['tenant_id']);

        // Send notification
        $notificationModel->sendEnrollmentConfirmation(
            $user['tenant_id'],
            $user['id'],
            $user['name'],
            $course['title']
        );

        $this->flash('success', 'Successfully enrolled in ' . $course['title']);
        $this->redirect('learn/' . $course['slug']);
    }

    /**
     * Mark lesson as completed
     *
     * @param int $id Lesson ID
     */
    public function completeLesson($id)
    {
        $this->requireRole('student');

        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $user = $this->getUser();
        $lessonModel = $this->model('Lesson');
        $enrollmentModel = $this->model('Enrollment');
        $lessonProgressModel = $this->model('LessonProgress');

        $lesson = $lessonModel->find($id);

        if (!$lesson) {
            $this->json(['success' => false, 'message' => 'Lesson not found'], 404);
        }

        // Get enrollment
        $enrollment = $enrollmentModel->getEnrollment($user['id'], $lesson['course_id']);

        if (!$enrollment) {
            $this->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        // Mark lesson as completed
        $lessonProgressModel->markCompleted($enrollment['id'], $id);

        // Check if course is complete
        $updatedEnrollment = $enrollmentModel->find($enrollment['id']);

        if ($updatedEnrollment['status'] === 'completed') {
            // Generate certificate
            $certificateModel = $this->model('Certificate');
            $certificateModel->generateCertificate(
                $enrollment['id'],
                $user['tenant_id'],
                $user['id'],
                $lesson['course_id']
            );

            // Send notification
            $courseModel = $this->model('Course');
            $course = $courseModel->find($lesson['course_id']);

            $notificationModel = $this->model('Notification');
            $notificationModel->sendCourseCompletion(
                $user['tenant_id'],
                $user['id'],
                $user['name'],
                $course['title']
            );
        }

        $this->json([
            'success' => true,
            'progress' => $updatedEnrollment['progress_percent'],
            'completed' => $updatedEnrollment['status'] === 'completed'
        ]);
    }

    /**
     * Show quiz
     *
     * @param int $id Quiz ID
     */
    public function quiz($id)
    {
        $this->requireRole('student');

        $user = $this->getUser();
        $quizModel = $this->model('Quiz');
        $enrollmentModel = $this->model('Enrollment');

        $quiz = $quizModel->getQuizWithQuestions($id);

        if (!$quiz) {
            $this->flash('error', 'Quiz not found.');
            $this->redirect('my-courses');
        }

        // Check enrollment
        $enrollment = $enrollmentModel->getEnrollment($user['id'], $quiz['course_id']);

        if (!$enrollment) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('catalog');
        }

        // Check if can attempt
        if (!$quizModel->canAttempt($id, $user['id'])) {
            $this->flash('error', 'You have reached the maximum number of attempts for this quiz.');
            $this->redirect('my-courses');
        }

        // Get previous attempts
        $attempts = $quizModel->getStudentAttempts($id, $user['id']);

        $this->view('student/quiz', [
            'quiz' => $quiz,
            'enrollment' => $enrollment,
            'attempts' => $attempts,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Submit quiz answers
     *
     * @param int $id Quiz ID
     */
    public function submitQuiz($id)
    {
        $this->requireRole('student');

        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('quiz/' . $id);
        }

        $user = $this->getUser();
        $quizModel = $this->model('Quiz');
        $enrollmentModel = $this->model('Enrollment');
        $quizAttemptModel = $this->model('QuizAttempt');

        $quiz = $quizModel->find($id);

        if (!$quiz) {
            $this->flash('error', 'Quiz not found.');
            $this->redirect('my-courses');
        }

        // Check enrollment
        $enrollment = $enrollmentModel->getEnrollment($user['id'], $quiz['course_id']);

        if (!$enrollment) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('catalog');
        }

        // Get answers from POST
        $answers = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'answer_') === 0) {
                $questionId = str_replace('answer_', '', $key);
                $answers[$questionId] = $value;
            }
        }

        // Create attempt
        $attemptId = $quizAttemptModel->createAttempt([
            'quiz_id' => $id,
            'student_id' => $user['id'],
            'enrollment_id' => $enrollment['id'],
            'answers' => $answers,
            'time_spent_seconds' => $this->input('time_spent', 0)
        ]);

        $attempt = $quizAttemptModel->find($attemptId);

        if ($attempt['passed']) {
            $this->flash('success', "Congratulations! You passed with {$attempt['percentage']}%");
        } else {
            $this->flash('warning', "You scored {$attempt['percentage']}%. Passing score is {$quiz['passing_score']}%");
        }

        $courseModel = $this->model('Course');
        $course = $courseModel->find($quiz['course_id']);

        $this->redirect('learn/' . $course['slug']);
    }

    /**
     * View certificate
     *
     * @param int $id Certificate ID
     */
    public function certificate($id)
    {
        $this->requireRole('student');

        $user = $this->getUser();
        $certificateModel = $this->model('Certificate');

        $certificate = $certificateModel->getCertificateWithDetails($id);

        if (!$certificate || $certificate['student_id'] != $user['id']) {
            $this->flash('error', 'Certificate not found.');
            $this->redirect('my-courses');
        }

        $this->view('student/certificate', [
            'certificate' => $certificate
        ]);
    }
}
