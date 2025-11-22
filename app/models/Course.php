<?php
// FILE: /app/models/Course.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Course Model
 */
class Course extends Model
{
    protected $table = 'courses';

    /**
     * Find course by slug and tenant
     *
     * @param string $slug
     * @param int $tenantId
     * @return array|null
     */
    public function findBySlug($slug, $tenantId)
    {
        return $this->findOne(['slug' => $slug, 'tenant_id' => $tenantId]);
    }

    /**
     * Get published courses for tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPublishedCourses($tenantId, $filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT c.*, cat.name as category_name, u.name as instructor_name,
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollments_count
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE c.tenant_id = :tenant_id
                AND c.status = 'published'";

        $params = ['tenant_id' => $tenantId];

        // Apply filters
        if (!empty($filters['category_id'])) {
            $sql .= " AND c.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['level'])) {
            $sql .= " AND c.level = :level";
            $params['level'] = $filters['level'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.title LIKE :search OR c.short_description LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (isset($filters['is_free'])) {
            if ($filters['is_free']) {
                $sql .= " AND c.price = 0";
            } else {
                $sql .= " AND c.price > 0";
            }
        }

        $sql .= " ORDER BY c.is_featured DESC, c.published_at DESC";
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        return $this->query($sql, $params);
    }

    /**
     * Count published courses
     *
     * @param int $tenantId
     * @param array $filters
     * @return int
     */
    public function countPublishedCourses($tenantId, $filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM courses
                WHERE tenant_id = :tenant_id AND status = 'published'";

        $params = ['tenant_id' => $tenantId];

        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['level'])) {
            $sql .= " AND level = :level";
            $params['level'] = $filters['level'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR short_description LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        $result = $this->queryOne($sql, $params);
        return (int)$result['count'];
    }

    /**
     * Get course with details
     *
     * @param int $courseId
     * @return array|null
     */
    public function getCourseWithDetails($courseId)
    {
        $sql = "SELECT c.*, cat.name as category_name, u.name as instructor_name, u.bio as instructor_bio,
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollments_count,
                       (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lessons_count
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE c.id = :id
                LIMIT 1";

        return $this->queryOne($sql, ['id' => $courseId]);
    }

    /**
     * Get courses by instructor
     *
     * @param int $instructorId
     * @param int $tenantId
     * @return array
     */
    public function getCoursesByInstructor($instructorId, $tenantId)
    {
        $sql = "SELECT c.*, cat.name as category_name,
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollments_count
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.instructor_id = :instructor_id
                AND c.tenant_id = :tenant_id
                ORDER BY c.created_at DESC";

        return $this->query($sql, [
            'instructor_id' => $instructorId,
            'tenant_id' => $tenantId
        ]);
    }

    /**
     * Get all courses for tenant (admin view)
     *
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCoursesByTenant($tenantId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT c.*, cat.name as category_name, u.name as instructor_name,
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollments_count
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE c.tenant_id = :tenant_id
                ORDER BY c.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->query($sql, ['tenant_id' => $tenantId]);
    }

    /**
     * Create course with unique slug
     *
     * @param array $data
     * @return int
     */
    public function createCourse($data)
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title'], $data['tenant_id']);
        }

        return $this->insert($data);
    }

    /**
     * Generate unique slug
     *
     * @param string $title
     * @param int $tenantId
     * @return string
     */
    private function generateSlug($title, $tenantId)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;

        while ($this->findBySlug($slug, $tenantId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get featured courses
     *
     * @param int $tenantId
     * @param int $limit
     * @return array
     */
    public function getFeaturedCourses($tenantId, $limit = 6)
    {
        return $this->findAll(
            ['tenant_id' => $tenantId, 'status' => 'published', 'is_featured' => 1],
            'published_at DESC',
            $limit
        );
    }
}
