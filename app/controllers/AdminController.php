<?php
// FILE: /app/controllers/AdminController.php

require_once __DIR__ . '/../core/Controller.php';

class AdminController extends Controller
{
    public function students()
    {
        $this->requireRole(['tenant_admin']);
        $user = $this->getUser();

        $userModel = $this->model('User');

        $page = max(1, (int)$this->input('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $students = $userModel->getStudents($user['tenant_id'], $perPage, $offset);
        $total = $userModel->countByTenant($user['tenant_id'], 'student');

        $pagination = paginate($total, $page, $perPage);

        $this->view('admin/students/index', [
            'students' => $students,
            'pagination' => $pagination
        ]);
    }

    public function createStudent()
    {
        $this->requireRole(['tenant_admin']);

        $this->view('admin/students/create', [
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function storeStudent()
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/students/create');
        }

        $user = $this->getUser();
        $userModel = $this->model('User');

        $userModel->createUser([
            'tenant_id' => $user['tenant_id'],
            'email' => $this->sanitize($this->input('email')),
            'password' => $this->input('password'),
            'name' => $this->sanitize($this->input('name')),
            'role' => 'student'
        ]);

        $tenantUsageModel = $this->model('TenantUsage');
        $tenantUsageModel->recalculateUsage($user['tenant_id']);

        $this->flash('success', 'Student created successfully.');
        $this->redirect('admin/students');
    }

    public function editStudent($id)
    {
        $this->requireRole(['tenant_admin']);
        $userModel = $this->model('User');

        $student = $userModel->find($id);

        $this->view('admin/students/edit', [
            'student' => $student,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function updateStudent($id)
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/students/' . $id . '/edit');
        }

        $userModel = $this->model('User');

        $data = [
            'name' => $this->sanitize($this->input('name')),
            'email' => $this->sanitize($this->input('email')),
            'status' => $this->input('status')
        ];

        if ($this->input('password')) {
            $data['password_hash'] = password_hash($this->input('password'), PASSWORD_DEFAULT);
        }

        $userModel->update($id, $data);

        $this->flash('success', 'Student updated successfully.');
        $this->redirect('admin/students');
    }
}
