<?php
// FILE: /app/models/Invoice.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Invoice Model
 */
class Invoice extends Model
{
    protected $table = 'invoices';

    /**
     * Get invoices by tenant
     *
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getInvoicesByTenant($tenantId, $limit = 20, $offset = 0)
    {
        return $this->findAll(
            ['tenant_id' => $tenantId],
            'created_at DESC',
            $limit,
            $offset
        );
    }

    /**
     * Generate invoice number
     *
     * @return string
     */
    public function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Ym') . '-';

        $sql = "SELECT MAX(CAST(SUBSTRING(invoice_number, -4) AS UNSIGNED)) as max_num
                FROM invoices
                WHERE invoice_number LIKE :prefix";

        $result = $this->queryOne($sql, ['prefix' => $prefix . '%']);
        $nextNum = ($result['max_num'] ?? 0) + 1;

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create invoice
     *
     * @param array $data
     * @return int
     */
    public function createInvoice($data)
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = $this->generateInvoiceNumber();
        }

        $data['status'] = $data['status'] ?? 'pending';
        $data['currency'] = $data['currency'] ?? 'USD';

        return $this->insert($data);
    }
}
