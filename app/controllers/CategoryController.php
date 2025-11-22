<?php
// FILE: /app/controllers/CategoryController.php

require_once __DIR__ . '/../core/Controller.php';

class CategoryController extends Controller
{
    public function index()
    {
        $this->requireRole(['tenant_admin']);
        $user = $this->getUser();
        $categoryModel = $this->model('Category');

        $categories = $categoryModel->getCategoriesWithCourseCount($user['tenant_id']);

        $this->view('admin/categories/index', [
            'categories' => $categories,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function store()
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $user = $this->getUser();
        $categoryModel = $this->model('Category');

        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->input('name')));

        $categoryModel->insert([
            'tenant_id' => $user['tenant_id'],
            'name' => $this->sanitize($this->input('name')),
            'slug' => $slug,
            'description' => $this->sanitize($this->input('description'))
        ]);

        $this->json(['success' => true]);
    }

    public function update($id)
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $categoryModel = $this->model('Category');

        $categoryModel->update($id, [
            'name' => $this->sanitize($this->input('name')),
            'description' => $this->sanitize($this->input('description'))
        ]);

        $this->json(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireRole(['tenant_admin']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $categoryModel = $this->model('Category');
        $categoryModel->delete($id);

        $this->json(['success' => true]);
    }
}
