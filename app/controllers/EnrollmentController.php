<?php
// FILE: /app/controllers/EnrollmentController.php

require_once __DIR__ . '/../core/Controller.php';

class EnrollmentController extends Controller
{
    public function index()
    {
        $this->requireRole(['tenant_admin', 'instructor']);
        $user = $this->getUser();

        $enrollmentModel = $this->model('Enrollment');

        $page = max(1, (int)$this->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $enrollments = $enrollmentModel->getEnrollmentsByTenant($user['tenant_id'], $perPage, $offset);
        $total = $enrollmentModel->countByTenant($user['tenant_id']);

        $pagination = paginate($total, $page, $perPage);

        $this->view('admin/enrollments/index', [
            'enrollments' => $enrollments,
            'pagination' => $pagination
        ]);
    }

    public function store()
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/enrollments');
        }

        $user = $this->getUser();
        $enrollmentModel = $this->model('Enrollment');

        $enrollmentModel->enrollStudent([
            'tenant_id' => $user['tenant_id'],
            'student_id' => $this->input('student_id'),
            'course_id' => $this->input('course_id'),
            'enrollment_type' => 'free'
        ]);

        $this->flash('success', 'Student enrolled successfully.');
        $this->redirect('admin/enrollments');
    }

    public function delete($id)
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $enrollmentModel = $this->model('Enrollment');
        $enrollmentModel->delete($id);

        $this->json(['success' => true]);
    }
}
