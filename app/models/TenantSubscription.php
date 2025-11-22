<?php
// FILE: /app/models/TenantSubscription.php

require_once __DIR__ . '/../core/Model.php';

/**
 * TenantSubscription Model
 */
class TenantSubscription extends Model
{
    protected $table = 'tenant_subscriptions';

    /**
     * Get active subscription for tenant
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getActiveSubscription($tenantId)
    {
        $sql = "SELECT ts.*, sp.*
                FROM tenant_subscriptions ts
                JOIN subscription_plans sp ON ts.plan_id = sp.id
                WHERE ts.tenant_id = :tenant_id
                AND ts.status IN ('active', 'trialing')
                ORDER BY ts.created_at DESC
                LIMIT 1";

        return $this->queryOne($sql, ['tenant_id' => $tenantId]);
    }

    /**
     * Check if tenant has active subscription
     *
     * @param int $tenantId
     * @return bool
     */
    public function hasActiveSubscription($tenantId)
    {
        $subscription = $this->getActiveSubscription($tenantId);
        return $subscription !== null;
    }

    /**
     * Get subscription history for tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getSubscriptionHistory($tenantId)
    {
        $sql = "SELECT ts.*, sp.name as plan_name
                FROM tenant_subscriptions ts
                JOIN subscription_plans sp ON ts.plan_id = sp.id
                WHERE ts.tenant_id = :tenant_id
                ORDER BY ts.created_at DESC";

        return $this->query($sql, ['tenant_id' => $tenantId]);
    }
}
