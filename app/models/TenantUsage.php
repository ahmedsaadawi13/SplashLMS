<?php
// FILE: /app/models/TenantUsage.php

require_once __DIR__ . '/../core/Model.php';

/**
 * TenantUsage Model
 */
class TenantUsage extends Model
{
    protected $table = 'tenant_usage';

    /**
     * Get usage for tenant
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getUsage($tenantId)
    {
        return $this->findOne(['tenant_id' => $tenantId]);
    }

    /**
     * Update usage counts
     *
     * @param int $tenantId
     */
    public function recalculateUsage($tenantId)
    {
        $sql = "UPDATE tenant_usage SET
                courses_count = (SELECT COUNT(*) FROM courses WHERE tenant_id = :tenant_id),
                students_count = (SELECT COUNT(*) FROM users WHERE tenant_id = :tenant_id AND role = 'student'),
                enrollments_count = (SELECT COUNT(*) FROM enrollments WHERE tenant_id = :tenant_id),
                last_calculated_at = NOW()
                WHERE tenant_id = :tenant_id";

        $this->execute($sql, ['tenant_id' => $tenantId]);
    }

    /**
     * Check if tenant can create course
     *
     * @param int $tenantId
     * @param int $maxCourses
     * @return bool
     */
    public function canCreateCourse($tenantId, $maxCourses)
    {
        $usage = $this->getUsage($tenantId);
        return $usage && $usage['courses_count'] < $maxCourses;
    }

    /**
     * Check if tenant can add student
     *
     * @param int $tenantId
     * @param int $maxStudents
     * @return bool
     */
    public function canAddStudent($tenantId, $maxStudents)
    {
        $usage = $this->getUsage($tenantId);
        return $usage && $usage['students_count'] < $maxStudents;
    }

    /**
     * Check if tenant can create enrollment
     *
     * @param int $tenantId
     * @param int $maxEnrollments
     * @return bool
     */
    public function canCreateEnrollment($tenantId, $maxEnrollments)
    {
        $usage = $this->getUsage($tenantId);
        return $usage && $usage['enrollments_count'] < $maxEnrollments;
    }

    /**
     * Update storage usage
     *
     * @param int $tenantId
     * @param int $sizeMb
     */
    public function addStorageUsage($tenantId, $sizeMb)
    {
        $sql = "UPDATE tenant_usage SET
                storage_used_mb = storage_used_mb + :size_mb
                WHERE tenant_id = :tenant_id";

        $this->execute($sql, [
            'tenant_id' => $tenantId,
            'size_mb' => $sizeMb
        ]);
    }
}
