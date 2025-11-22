<?php
// FILE: /app/models/QuizAttempt.php

require_once __DIR__ . '/../core/Model.php';

/**
 * QuizAttempt Model
 */
class QuizAttempt extends Model
{
    protected $table = 'quiz_attempts';

    /**
     * Create quiz attempt with grading
     *
     * @param array $data
     * @return int
     */
    public function createAttempt($data)
    {
        // Calculate score
        $quiz = new Quiz();
        $quizData = $quiz->getQuizWithQuestions($data['quiz_id']);

        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($quizData['questions'] as $question) {
            $totalPoints += $question['points'];

            $studentAnswer = $data['answers'][$question['id']] ?? null;
            if ($studentAnswer == $question['correct_answer']) {
                $earnedPoints += $question['points'];
            }
        }

        $percentage = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;

        $attemptData = [
            'quiz_id' => $data['quiz_id'],
            'student_id' => $data['student_id'],
            'enrollment_id' => $data['enrollment_id'],
            'score' => $earnedPoints,
            'max_score' => $totalPoints,
            'percentage' => round($percentage, 2),
            'passed' => $percentage >= $quizData['passing_score'] ? 1 : 0,
            'answers' => json_encode($data['answers']),
            'time_spent_seconds' => $data['time_spent_seconds'] ?? 0,
            'completed_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($attemptData);
    }

    /**
     * Get attempts by student and course
     *
     * @param int $studentId
     * @param int $courseId
     * @return array
     */
    public function getAttemptsByStudentAndCourse($studentId, $courseId)
    {
        $sql = "SELECT qa.*, q.title as quiz_title
                FROM quiz_attempts qa
                JOIN quizzes q ON qa.quiz_id = q.id
                WHERE qa.student_id = :student_id AND q.course_id = :course_id
                ORDER BY qa.started_at DESC";

        return $this->query($sql, [
            'student_id' => $studentId,
            'course_id' => $courseId
        ]);
    }
}
