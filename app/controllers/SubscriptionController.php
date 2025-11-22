<?php
// FILE: /app/controllers/SubscriptionController.php

require_once __DIR__ . '/../core/Controller.php';

class SubscriptionController extends Controller
{
    public function index()
    {
        $this->requireRole('tenant_admin');
        $user = $this->getUser();

        $subscriptionModel = $this->model('TenantSubscription');
        $usageModel = $this->model('TenantUsage');
        $planModel = $this->model('SubscriptionPlan');

        $subscription = $subscriptionModel->getActiveSubscription($user['tenant_id']);
        $usage = $usageModel->getUsage($user['tenant_id']);
        $availablePlans = $planModel->getActivePlans();

        $this->view('admin/subscription/index', [
            'subscription' => $subscription,
            'usage' => $usage,
            'availablePlans' => $availablePlans,
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    public function invoices()
    {
        $this->requireRole('tenant_admin');
        $user = $this->getUser();

        $invoiceModel = $this->model('Invoice');
        $invoices = $invoiceModel->getInvoicesByTenant($user['tenant_id']);

        $this->view('admin/subscription/invoices', [
            'invoices' => $invoices
        ]);
    }

    public function viewInvoice($id)
    {
        $this->requireRole('tenant_admin');
        $user = $this->getUser();

        $invoiceModel = $this->model('Invoice');
        $invoice = $invoiceModel->find($id);

        if (!$invoice || $invoice['tenant_id'] != $user['tenant_id']) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect('admin/invoices');
        }

        $this->view('admin/subscription/invoice-detail', [
            'invoice' => $invoice
        ]);
    }

    public function upgrade()
    {
        $this->requireRole('tenant_admin');

        if (!$this->validateCSRF()) {
            $this->redirect('admin/subscription');
        }

        $user = $this->getUser();
        $planId = $this->input('plan_id');

        // Create subscription (simplified - would integrate with payment gateway)
        $subscriptionModel = $this->model('TenantSubscription');

        $subscriptionModel->insert([
            'tenant_id' => $user['tenant_id'],
            'plan_id' => $planId,
            'status' => 'active',
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
        ]);

        $this->flash('success', 'Subscription upgraded successfully.');
        $this->redirect('admin/subscription');
    }
}
