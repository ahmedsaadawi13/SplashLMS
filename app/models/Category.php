<?php
// FILE: /app/models/Category.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Category Model
 */
class Category extends Model
{
    protected $table = 'categories';

    /**
     * Get categories by tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getCategoriesByTenant($tenantId)
    {
        return $this->findAll(['tenant_id' => $tenantId], 'display_order ASC, name ASC');
    }

    /**
     * Get category by slug
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
     * Get categories with course count
     *
     * @param int $tenantId
     * @return array
     */
    public function getCategoriesWithCourseCount($tenantId)
    {
        $sql = "SELECT c.*, COUNT(co.id) as courses_count
                FROM categories c
                LEFT JOIN courses co ON c.id = co.category_id AND co.status = 'published'
                WHERE c.tenant_id = :tenant_id
                GROUP BY c.id
                ORDER BY c.display_order ASC, c.name ASC";

        return $this->query($sql, ['tenant_id' => $tenantId]);
    }
}
