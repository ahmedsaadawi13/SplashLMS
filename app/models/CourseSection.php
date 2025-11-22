<?php
// FILE: /app/models/CourseSection.php

require_once __DIR__ . '/../core/Model.php';

/**
 * CourseSection Model
 */
class CourseSection extends Model
{
    protected $table = 'course_sections';

    /**
     * Get sections by course
     *
     * @param int $courseId
     * @return array
     */
    public function getSectionsByCourse($courseId)
    {
        return $this->findAll(['course_id' => $courseId], 'display_order ASC');
    }

    /**
     * Get sections with lessons
     *
     * @param int $courseId
     * @return array
     */
    public function getSectionsWithLessons($courseId)
    {
        $sections = $this->getSectionsByCourse($courseId);

        foreach ($sections as &$section) {
            $sql = "SELECT * FROM lessons
                    WHERE section_id = :section_id
                    ORDER BY display_order ASC";

            $section['lessons'] = $this->query($sql, ['section_id' => $section['id']]);
        }

        return $sections;
    }

    /**
     * Get next display order for course
     *
     * @param int $courseId
     * @return int
     */
    public function getNextDisplayOrder($courseId)
    {
        $sql = "SELECT MAX(display_order) as max_order FROM course_sections WHERE course_id = :course_id";
        $result = $this->queryOne($sql, ['course_id' => $courseId]);

        return ($result['max_order'] ?? 0) + 1;
    }
}
