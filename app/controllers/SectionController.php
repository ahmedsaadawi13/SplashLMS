<?php
// FILE: /app/controllers/SectionController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * SectionController - Course section management
 */
class SectionController extends Controller
{
    public function store($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $user = $this->getUser();
        $sectionModel = $this->model('CourseSection');

        $displayOrder = $sectionModel->getNextDisplayOrder($id);

        $sectionId = $sectionModel->insert([
            'course_id' => $id,
            'title' => $this->sanitize($this->input('title')),
            'description' => $this->sanitize($this->input('description')),
            'display_order' => $displayOrder
        ]);

        $this->json(['success' => true, 'id' => $sectionId]);
    }

    public function update($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $sectionModel = $this->model('CourseSection');

        $sectionModel->update($id, [
            'title' => $this->sanitize($this->input('title')),
            'description' => $this->sanitize($this->input('description'))
        ]);

        $this->json(['success' => true]);
    }

    public function delete($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $sectionModel = $this->model('CourseSection');
        $sectionModel->delete($id);

        $this->json(['success' => true]);
    }
}
