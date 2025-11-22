<?php
// FILE: /app/controllers/LessonController.php

require_once __DIR__ . '/../core/Controller.php';

class LessonController extends Controller
{
    public function create($courseId)
    {
        $this->requireRole(['tenant_admin', 'instructor']);
        $sectionModel = $this->model('CourseSection');
        $sections = $sectionModel->getSectionsByCourse($courseId);

        $this->view('admin/lessons/create', [
            'courseId' => $courseId,
            'sections' => $sections,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function store($courseId)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/courses/' . $courseId . '/edit');
        }

        $lessonModel = $this->model('Lesson');
        $sectionId = $this->input('section_id');

        $displayOrder = $lessonModel->getNextDisplayOrder($sectionId);

        $lessonModel->insert([
            'course_id' => $courseId,
            'section_id' => $sectionId,
            'title' => $this->sanitize($this->input('title')),
            'type' => $this->input('type'),
            'content' => $this->input('content'),
            'video_url' => $this->sanitize($this->input('video_url')),
            'duration_minutes' => (int)$this->input('duration_minutes'),
            'display_order' => $displayOrder,
            'is_preview' => $this->input('is_preview') ? 1 : 0
        ]);

        $this->flash('success', 'Lesson created successfully.');
        $this->redirect('admin/courses/' . $courseId . '/edit');
    }

    public function edit($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);
        $lessonModel = $this->model('Lesson');
        $sectionModel = $this->model('CourseSection');

        $lesson = $lessonModel->find($id);
        $sections = $sectionModel->getSectionsByCourse($lesson['course_id']);

        $this->view('admin/lessons/edit', [
            'lesson' => $lesson,
            'sections' => $sections,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function update($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/lessons/' . $id . '/edit');
        }

        $lessonModel = $this->model('Lesson');
        $lesson = $lessonModel->find($id);

        $lessonModel->update($id, [
            'section_id' => $this->input('section_id'),
            'title' => $this->sanitize($this->input('title')),
            'type' => $this->input('type'),
            'content' => $this->input('content'),
            'video_url' => $this->sanitize($this->input('video_url')),
            'duration_minutes' => (int)$this->input('duration_minutes'),
            'is_preview' => $this->input('is_preview') ? 1 : 0
        ]);

        $this->flash('success', 'Lesson updated successfully.');
        $this->redirect('admin/courses/' . $lesson['course_id'] . '/edit');
    }

    public function delete($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $lessonModel = $this->model('Lesson');
        $lesson = $lessonModel->find($id);
        $courseId = $lesson['course_id'];

        $lessonModel->delete($id);

        $this->json(['success' => true, 'courseId' => $courseId]);
    }
}
