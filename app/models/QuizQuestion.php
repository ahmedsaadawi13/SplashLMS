<?php
// FILE: /app/models/QuizQuestion.php

require_once __DIR__ . '/../core/Model.php';

/**
 * QuizQuestion Model
 */
class QuizQuestion extends Model
{
    protected $table = 'quiz_questions';

    /**
     * Get questions by quiz
     *
     * @param int $quizId
     * @param bool $randomize
     * @return array
     */
    public function getQuestionsByQuiz($quizId, $randomize = false)
    {
        $orderBy = $randomize ? 'RAND()' : 'display_order ASC';
        return $this->findAll(['quiz_id' => $quizId], $orderBy);
    }

    /**
     * Get next display order
     *
     * @param int $quizId
     * @return int
     */
    public function getNextDisplayOrder($quizId)
    {
        $sql = "SELECT MAX(display_order) as max_order FROM quiz_questions WHERE quiz_id = :quiz_id";
        $result = $this->queryOne($sql, ['quiz_id' => $quizId]);

        return ($result['max_order'] ?? 0) + 1;
    }
}
