<?php
// FILE: /app/models/Payment.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Payment Model
 */
class Payment extends Model
{
    protected $table = 'payments';

    /**
     * Create payment
     *
     * @param array $data
     * @return int
     */
    public function createPayment($data)
    {
        $data['status'] = $data['status'] ?? 'pending';
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['transaction_id'] = $data['transaction_id'] ?? 'TXN-' . strtoupper(bin2hex(random_bytes(8)));

        return $this->insert($data);
    }

    /**
     * Simulate payment completion
     *
     * @param int $paymentId
     * @return bool
     */
    public function completePayment($paymentId)
    {
        return $this->update($paymentId, ['status' => 'completed']);
    }

    /**
     * Get payments by tenant
     *
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPaymentsByTenant($tenantId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT p.*, i.invoice_number
                FROM payments p
                LEFT JOIN invoices i ON p.invoice_id = i.id
                WHERE p.tenant_id = :tenant_id
                ORDER BY p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->query($sql, ['tenant_id' => $tenantId]);
    }
}
