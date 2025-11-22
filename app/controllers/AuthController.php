<?php
// FILE: /app/controllers/AuthController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * AuthController - Handles authentication
 */
class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function loginForm()
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
        }

        $this->view('auth/login', [
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Process login
     */
    public function login()
    {
        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request. Please try again.');
            $this->redirect('login');
        }

        $email = $this->sanitize($this->input('email'));
        $password = $this->input('password');
        $tenantSlug = $this->sanitize($this->input('tenant_slug'));

        // Validate inputs
        if (empty($email) || empty($password)) {
            $this->flash('error', 'Email and password are required.');
            $this->redirect('login');
        }

        // Get tenant ID if tenant slug provided
        $tenantId = null;
        if (!empty($tenantSlug)) {
            $tenantModel = $this->model('Tenant');
            $tenant = $tenantModel->findBySlug($tenantSlug);

            if (!$tenant) {
                $this->flash('error', 'Invalid organization.');
                $this->redirect('login');
            }

            $tenantId = $tenant['id'];
        }

        // Verify credentials
        $userModel = $this->model('User');
        $user = $userModel->verifyCredentials($email, $password, $tenantId);

        if (!$user) {
            $this->flash('error', 'Invalid credentials.');
            $this->redirect('login');
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];

        // Update last login
        $userModel->updateLastLogin($user['id']);

        // Redirect based on role
        if ($user['role'] === 'platform_admin') {
            $this->redirect('platform/dashboard');
        } else {
            $this->redirect('dashboard');
        }
    }

    /**
     * Show registration form
     */
    public function registerForm()
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
        }

        $this->view('auth/register', [
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Process registration
     */
    public function register()
    {
        if (!$this->validateCSRF()) {
            $this->flash('error', 'Invalid request. Please try again.');
            $this->redirect('register');
        }

        $name = $this->sanitize($this->input('name'));
        $email = $this->sanitize($this->input('email'));
        $password = $this->input('password');
        $confirmPassword = $this->input('confirm_password');
        $tenantName = $this->sanitize($this->input('tenant_name'));

        // Validate inputs
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($tenantName)) {
            $errors[] = 'Organization name is required.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode('<br>', $errors));
            $this->redirect('register');
        }

        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            // Create tenant
            $tenantModel = $this->model('Tenant');
            $tenantId = $tenantModel->createTenant([
                'name' => $tenantName,
                'contact_email' => $email
            ]);

            // Create user as tenant admin
            $userModel = $this->model('User');
            $userId = $userModel->createUser([
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'role' => 'tenant_admin',
                'email_verified_at' => date('Y-m-d H:i:s')
            ]);

            // Create tenant usage record
            $usageModel = $this->model('TenantUsage');
            $usageModel->insert([
                'tenant_id' => $tenantId,
                'courses_count' => 0,
                'students_count' => 0,
                'enrollments_count' => 0,
                'storage_used_mb' => 0
            ]);

            // Assign free trial subscription
            $planModel = $this->model('SubscriptionPlan');
            $freePlan = $planModel->findOne(['name' => 'Free Trial']);

            if ($freePlan) {
                $subscriptionModel = $this->model('TenantSubscription');
                $subscriptionModel->insert([
                    'tenant_id' => $tenantId,
                    'plan_id' => $freePlan['id'],
                    'status' => 'trialing',
                    'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
                    'current_period_start' => date('Y-m-d H:i:s'),
                    'current_period_end' => date('Y-m-d H:i:s', strtotime('+14 days'))
                ]);
            }

            $db->commit();

            // Auto login
            $_SESSION['user_id'] = $userId;
            $_SESSION['tenant_id'] = $tenantId;
            $_SESSION['role'] = 'tenant_admin';
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;

            $this->flash('success', 'Account created successfully! Welcome to SplashLMS.');
            $this->redirect('dashboard');

        } catch (Exception $e) {
            $db->rollBack();
            $this->flash('error', 'Registration failed. Please try again.');
            $this->redirect('register');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        session_destroy();
        $this->redirect('login');
    }

    /**
     * Show forgot password form
     */
    public function forgotPasswordForm()
    {
        $this->view('auth/forgot-password', [
            'csrf_token' => $this->generateCSRF()
        ]);
    }

    /**
     * Process forgot password
     */
    public function forgotPassword()
    {
        // Placeholder for password reset functionality
        $this->flash('success', 'Password reset instructions have been sent to your email.');
        $this->redirect('login');
    }
}
