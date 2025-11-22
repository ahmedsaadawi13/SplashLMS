<?php
// FILE: /app/models/Lesson.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Lesson Model
 */
class Lesson extends Model
{
    protected $table = 'lessons';

    /**
     * Get lessons by course
     *
     * @param int $courseId
     * @return array
     */
    public function getLessonsByCourse($courseId)
    {
        $sql = "SELECT l.*, cs.title as section_title
                FROM lessons l
                LEFT JOIN course_sections cs ON l.section_id = cs.id
                WHERE l.course_id = :course_id
                ORDER BY cs.display_order ASC, l.display_order ASC";

        return $this->query($sql, ['course_id' => $courseId]);
    }

    /**
     * Get lessons by section
     *
     * @param int $sectionId
     * @return array
     */
    public function getLessonsBySection($sectionId)
    {
        return $this->findAll(['section_id' => $sectionId], 'display_order ASC');
    }

    /**
     * Get next display order for section
     *
     * @param int $sectionId
     * @return int
     */
    public function getNextDisplayOrder($sectionId)
    {
        $sql = "SELECT MAX(display_order) as max_order FROM lessons WHERE section_id = :section_id";
        $result = $this->queryOne($sql, ['section_id' => $sectionId]);

        return ($result['max_order'] ?? 0) + 1;
    }

    /**
     * Get preview lessons for a course
     *
     * @param int $courseId
     * @return array
     */
    public function getPreviewLessons($courseId)
    {
        return $this->findAll(
            ['course_id' => $courseId, 'is_preview' => 1, 'is_published' => 1],
            'display_order ASC'
        );
    }

    /**
     * Count lessons by course
     *
     * @param int $courseId
     * @return int
     */
    public function countLessonsByCourse($courseId)
    {
        return $this->count(['course_id' => $courseId, 'is_published' => 1]);
    }
}
