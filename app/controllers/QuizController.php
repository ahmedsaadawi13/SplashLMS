<?php
// FILE: /app/controllers/QuizController.php

require_once __DIR__ . '/../core/Controller.php';

class QuizController extends Controller
{
    public function create($courseId)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        $this->view('admin/quizzes/create', [
            'courseId' => $courseId,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function store($courseId)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/courses/' . $courseId . '/edit');
        }

        $user = $this->getUser();
        $quizModel = $this->model('Quiz');

        $quizModel->insert([
            'tenant_id' => $user['tenant_id'],
            'course_id' => $courseId,
            'title' => $this->sanitize($this->input('title')),
            'description' => $this->sanitize($this->input('description')),
            'passing_score' => (float)$this->input('passing_score', 70),
            'max_attempts' => (int)$this->input('max_attempts', 0),
            'is_required' => $this->input('is_required') ? 1 : 0
        ]);

        $this->flash('success', 'Quiz created successfully.');
        $this->redirect('admin/courses/' . $courseId . '/edit');
    }

    public function edit($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        $quizModel = $this->model('Quiz');
        $questionModel = $this->model('QuizQuestion');

        $quiz = $quizModel->find($id);
        $questions = $questionModel->getQuestionsByQuiz($id);

        $this->view('admin/quizzes/edit', [
            'quiz' => $quiz,
            'questions' => $questions,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function update($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->redirect('admin/quizzes/' . $id . '/edit');
        }

        $quizModel = $this->model('Quiz');

        $quizModel->update($id, [
            'title' => $this->sanitize($this->input('title')),
            'description' => $this->sanitize($this->input('description')),
            'passing_score' => (float)$this->input('passing_score'),
            'max_attempts' => (int)$this->input('max_attempts'),
            'is_required' => $this->input('is_required') ? 1 : 0
        ]);

        $this->flash('success', 'Quiz updated successfully.');
        $this->redirect('admin/quizzes/' . $id . '/edit');
    }

    public function delete($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $quizModel = $this->model('Quiz');
        $quiz = $quizModel->find($id);
        $courseId = $quiz['course_id'];

        $quizModel->delete($id);

        $this->json(['success' => true, 'courseId' => $courseId]);
    }

    public function storeQuestion($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $questionModel = $this->model('QuizQuestion');

        $options = [
            $this->sanitize($this->input('option_0')),
            $this->sanitize($this->input('option_1')),
            $this->sanitize($this->input('option_2')),
            $this->sanitize($this->input('option_3'))
        ];

        $displayOrder = $questionModel->getNextDisplayOrder($id);

        $questionModel->insert([
            'quiz_id' => $id,
            'question_text' => $this->sanitize($this->input('question_text')),
            'question_type' => 'multiple_choice',
            'options' => json_encode($options),
            'correct_answer' => $this->input('correct_answer'),
            'points' => (float)$this->input('points', 1),
            'display_order' => $displayOrder
        ]);

        $this->json(['success' => true]);
    }

    public function updateQuestion($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $questionModel = $this->model('QuizQuestion');

        $options = [
            $this->sanitize($this->input('option_0')),
            $this->sanitize($this->input('option_1')),
            $this->sanitize($this->input('option_2')),
            $this->sanitize($this->input('option_3'))
        ];

        $questionModel->update($id, [
            'question_text' => $this->sanitize($this->input('question_text')),
            'options' => json_encode($options),
            'correct_answer' => $this->input('correct_answer'),
            'points' => (float)$this->input('points')
        ]);

        $this->json(['success' => true]);
    }

    public function deleteQuestion($id)
    {
        $this->requireRole(['tenant_admin', 'instructor']);

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $questionModel = $this->model('QuizQuestion');
        $questionModel->delete($id);

        $this->json(['success' => true]);
    }
}
