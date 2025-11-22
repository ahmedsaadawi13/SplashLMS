<?php
// FILE: /app/models/SubscriptionPlan.php

require_once __DIR__ . '/../core/Model.php';

/**
 * SubscriptionPlan Model
 */
class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    /**
     * Get active plans
     *
     * @return array
     */
    public function getActivePlans()
    {
        return $this->findAll(['is_active' => 1], 'price ASC');
    }

    /**
     * Get plan features as array
     *
     * @param array $plan
     * @return array
     */
    public function getFeatures($plan)
    {
        if (empty($plan['features'])) {
            return [];
        }

        return json_decode($plan['features'], true) ?: [];
    }
}
