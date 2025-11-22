<?php
// FILE: /app/models/Tenant.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Tenant Model
 * Represents organizations/academies/instructors
 */
class Tenant extends Model
{
    protected $table = 'tenants';

    /**
     * Find tenant by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug($slug)
    {
        return $this->findOne(['slug' => $slug]);
    }

    /**
     * Find tenant by API key
     *
     * @param string $apiKey
     * @return array|null
     */
    public function findByApiKey($apiKey)
    {
        return $this->findOne(['api_key' => $apiKey]);
    }

    /**
     * Get active tenants
     *
     * @return array
     */
    public function getActiveTenants()
    {
        return $this->findAll(['status' => 'active'], 'created_at DESC');
    }

    /**
     * Get tenant with subscription details
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getWithSubscription($tenantId)
    {
        $sql = "SELECT t.*, ts.status as subscription_status, ts.current_period_end,
                       sp.name as plan_name, sp.max_courses, sp.max_students,
                       sp.max_enrollments, sp.max_storage_mb
                FROM tenants t
                LEFT JOIN tenant_subscriptions ts ON t.id = ts.tenant_id AND ts.status = 'active'
                LEFT JOIN subscription_plans sp ON ts.plan_id = sp.id
                WHERE t.id = :tenant_id
                LIMIT 1";

        return $this->queryOne($sql, ['tenant_id' => $tenantId]);
    }

    /**
     * Generate unique API key
     *
     * @return string
     */
    public function generateApiKey()
    {
        do {
            $apiKey = 'sk_' . bin2hex(random_bytes(24));
            $exists = $this->findOne(['api_key' => $apiKey]);
        } while ($exists);

        return $apiKey;
    }

    /**
     * Create new tenant
     *
     * @param array $data
     * @return int Tenant ID
     */
    public function createTenant($data)
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Generate API key
        $data['api_key'] = $this->generateApiKey();
        $data['status'] = $data['status'] ?? 'active';
        $data['default_language'] = $data['default_language'] ?? 'en';
        $data['default_currency'] = $data['default_currency'] ?? 'USD';
        $data['timezone'] = $data['timezone'] ?? 'UTC';

        return $this->insert($data);
    }

    /**
     * Generate unique slug from name
     *
     * @param string $name
     * @return string
     */
    private function generateSlug($name)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $originalSlug = $slug;
        $counter = 1;

        while ($this->findBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
