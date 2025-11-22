<?php
// FILE: /app/controllers/PlatformController.php

require_once __DIR__ . '/../core/Controller.php';

class PlatformController extends Controller
{
    public function dashboard()
    {
        $this->requireRole('platform_admin');

        $tenantModel = $this->model('Tenant');
        $userModel = $this->model('User');
        $courseModel = $this->model('Course');

        $sql = "SELECT COUNT(*) as count FROM tenants";
        $db = Database::getInstance()->getConnection();
        $totalTenants = $db->query($sql)->fetch()['count'];

        $sql = "SELECT COUNT(*) as count FROM users";
        $totalUsers = $db->query($sql)->fetch()['count'];

        $sql = "SELECT COUNT(*) as count FROM courses";
        $totalCourses = $db->query($sql)->fetch()['count'];

        $sql = "SELECT COUNT(*) as count FROM enrollments";
        $totalEnrollments = $db->query($sql)->fetch()['count'];

        $tenants = $tenantModel->findAll([], 'created_at DESC', 10);

        $this->view('platform/dashboard', [
            'totalTenants' => $totalTenants,
            'totalUsers' => $totalUsers,
            'totalCourses' => $totalCourses,
            'totalEnrollments' => $totalEnrollments,
            'tenants' => $tenants
        ]);
    }

    public function tenants()
    {
        $this->requireRole('platform_admin');

        $tenantModel = $this->model('Tenant');
        $tenants = $tenantModel->getActiveTenants();

        $this->view('platform/tenants/index', [
            'tenants' => $tenants
        ]);
    }

    public function createTenant()
    {
        $this->requireRole('platform_admin');

        $this->view('platform/tenants/create', [
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function storeTenant()
    {
        $this->requireRole('platform_admin');

        if (!$this->validateCSRF()) {
            $this->redirect('platform/tenants/create');
        }

        $tenantModel = $this->model('Tenant');

        $tenantModel->createTenant([
            'name' => $this->sanitize($this->input('name')),
            'contact_email' => $this->sanitize($this->input('contact_email'))
        ]);

        $this->flash('success', 'Tenant created successfully.');
        $this->redirect('platform/tenants');
    }

    public function editTenant($id)
    {
        $this->requireRole('platform_admin');

        $tenantModel = $this->model('Tenant');
        $tenant = $tenantModel->find($id);

        $this->view('platform/tenants/edit', [
            'tenant' => $tenant,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function updateTenant($id)
    {
        $this->requireRole('platform_admin');

        if (!$this->validateCSRF()) {
            $this->redirect('platform/tenants/' . $id . '/edit');
        }

        $tenantModel = $this->model('Tenant');

        $tenantModel->update($id, [
            'name' => $this->sanitize($this->input('name')),
            'status' => $this->input('status'),
            'contact_email' => $this->sanitize($this->input('contact_email'))
        ]);

        $this->flash('success', 'Tenant updated successfully.');
        $this->redirect('platform/tenants');
    }

    public function plans()
    {
        $this->requireRole('platform_admin');

        $planModel = $this->model('SubscriptionPlan');
        $plans = $planModel->findAll([], 'price ASC');

        $this->view('platform/plans/index', [
            'plans' => $plans
        ]);
    }
}
