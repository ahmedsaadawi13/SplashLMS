<?php
// FILE: /app/controllers/PaymentController.php

require_once __DIR__ . '/../core/Controller.php';

class PaymentController extends Controller
{
    public function simulate()
    {
        $this->requireAuth();

        if (!$this->validateCSRF()) {
            $this->json(['success' => false], 400);
        }

        $user = $this->getUser();
        $paymentModel = $this->model('Payment');
        $invoiceModel = $this->model('Invoice');

        $amount = (float)$this->input('amount');
        $invoiceId = $this->input('invoice_id');

        // Create payment
        $paymentId = $paymentModel->createPayment([
            'tenant_id' => $user['tenant_id'],
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_method' => 'simulated'
        ]);

        // Mark payment as completed
        $paymentModel->completePayment($paymentId);

        // Update invoice status
        if ($invoiceId) {
            $invoiceModel->update($invoiceId, [
                'status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->json(['success' => true, 'payment_id' => $paymentId]);
    }
}
