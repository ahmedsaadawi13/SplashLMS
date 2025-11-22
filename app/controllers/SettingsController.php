<?php
// FILE: /app/controllers/SettingsController.php

require_once __DIR__ . '/../core/Controller.php';

class SettingsController extends Controller
{
    public function index()
    {
        $this->requireRole('tenant_admin');
        $user = $this->getUser();

        $tenantModel = $this->model('Tenant');
        $tenant = $tenantModel->find($user['tenant_id']);

        $this->view('admin/settings/index', [
            'tenant' => $tenant,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function update()
    {
        $this->requireRole('tenant_admin');

        if (!$this->validateCSRF()) {
            $this->redirect('admin/settings');
        }

        $user = $this->getUser();
        $tenantModel = $this->model('Tenant');

        $tenantModel->update($user['tenant_id'], [
            'name' => $this->sanitize($this->input('name')),
            'description' => $this->sanitize($this->input('description')),
            'website' => $this->sanitize($this->input('website')),
            'contact_email' => $this->sanitize($this->input('contact_email')),
            'contact_phone' => $this->sanitize($this->input('contact_phone')),
            'address' => $this->sanitize($this->input('address'))
        ]);

        $this->flash('success', 'Settings updated successfully.');
        $this->redirect('admin/settings');
    }
}
