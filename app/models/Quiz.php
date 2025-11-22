<?php
// FILE: /app/models/Quiz.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Quiz Model
 */
class Quiz extends Model
{
    protected $table = 'quizzes';

    /**
     * Get quizzes by course
     *
     * @param int $courseId
     * @return array
     */
    public function getQuizzesByCourse($courseId)
    {
        return $this->findAll(['course_id' => $courseId], 'created_at ASC');
    }

    /**
     * Get quiz with questions
     *
     * @param int $quizId
     * @return array|null
     */
    public function getQuizWithQuestions($quizId)
    {
        $quiz = $this->find($quizId);
        if (!$quiz) {
            return null;
        }

        $sql = "SELECT * FROM quiz_questions
                WHERE quiz_id = :quiz_id
                ORDER BY display_order ASC";

        $quiz['questions'] = $this->query($sql, ['quiz_id' => $quizId]);

        return $quiz;
    }

    /**
     * Get student attempts for quiz
     *
     * @param int $quizId
     * @param int $studentId
     * @return array
     */
    public function getStudentAttempts($quizId, $studentId)
    {
        $sql = "SELECT * FROM quiz_attempts
                WHERE quiz_id = :quiz_id AND student_id = :student_id
                ORDER BY started_at DESC";

        return $this->query($sql, [
            'quiz_id' => $quizId,
            'student_id' => $studentId
        ]);
    }

    /**
     * Check if student can attempt quiz
     *
     * @param int $quizId
     * @param int $studentId
     * @return bool
     */
    public function canAttempt($quizId, $studentId)
    {
        $quiz = $this->find($quizId);
        if (!$quiz) {
            return false;
        }

        // If unlimited attempts
        if ($quiz['max_attempts'] == 0) {
            return true;
        }

        // Check attempt count
        $attempts = $this->getStudentAttempts($quizId, $studentId);
        return count($attempts) < $quiz['max_attempts'];
    }

    /**
     * Get best attempt for student
     *
     * @param int $quizId
     * @param int $studentId
     * @return array|null
     */
    public function getBestAttempt($quizId, $studentId)
    {
        $sql = "SELECT * FROM quiz_attempts
                WHERE quiz_id = :quiz_id AND student_id = :student_id
                ORDER BY percentage DESC
                LIMIT 1";

        return $this->queryOne($sql, [
            'quiz_id' => $quizId,
            'student_id' => $studentId
        ]);
    }
}
