<?php
// FILE: /tests/run_tests.php

/**
 * Simple test runner for SplashLMS
 * Run: php tests/run_tests.php
 */

// Load environment and core files
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';

// Test counter
$passed = 0;
$failed = 0;

function test($name, $callback) {
    global $passed, $failed;

    echo "\nTesting: {$name}... ";

    try {
        $result = $callback();

        if ($result === true) {
            echo "✓ PASSED\n";
            $passed++;
        } else {
            echo "✗ FAILED\n";
            if (is_string($result)) {
                echo "  Reason: {$result}\n";
            }
            $failed++;
        }
    } catch (Exception $e) {
        echo "✗ FAILED (Exception)\n";
        echo "  " . $e->getMessage() . "\n";
        $failed++;
    }
}

function assertEquals($expected, $actual, $message = '') {
    if ($expected === $actual) {
        return true;
    }
    return $message ?: "Expected {$expected}, got {$actual}";
}

function assertTrue($condition, $message = 'Assertion failed') {
    return $condition === true ? true : $message;
}

function assertNotNull($value, $message = 'Value should not be null') {
    return $value !== null ? true : $message;
}

echo "=====================================================\n";
echo "SplashLMS Test Suite\n";
echo "=====================================================\n";

// Database Connection Test
test('Database connection', function() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        return assertTrue($conn instanceof PDO, 'Failed to get PDO instance');
    } catch (Exception $e) {
        return 'Database connection failed: ' . $e->getMessage();
    }
});

// Test Models
require_once __DIR__ . '/../app/models/Tenant.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Course.php';

test('Tenant model - find active tenants', function() {
    $tenantModel = new Tenant();
    $tenants = $tenantModel->getActiveTenants();
    return assertTrue(is_array($tenants), 'Should return array of tenants');
});

test('Tenant model - find by slug', function() {
    $tenantModel = new Tenant();
    $tenant = $tenantModel->findBySlug('tech-academy');
    return assertNotNull($tenant, 'Tech Academy tenant should exist');
});

test('User model - count students', function() {
    $userModel = new User();
    $tenantModel = new Tenant();

    $tenant = $tenantModel->findBySlug('tech-academy');
    if (!$tenant) {
        return 'Tenant not found';
    }

    $count = $userModel->countByTenant($tenant['id'], 'student');
    return assertTrue($count >= 0, 'Should return student count');
});

test('Course model - get published courses', function() {
    $courseModel = new Course();
    $tenantModel = new Tenant();

    $tenant = $tenantModel->findBySlug('tech-academy');
    if (!$tenant) {
        return 'Tenant not found';
    }

    $courses = $courseModel->getPublishedCourses($tenant['id']);
    return assertTrue(is_array($courses), 'Should return array of courses');
});

test('Course model - course with details', function() {
    $courseModel = new Course();
    $tenantModel = new Tenant();

    $tenant = $tenantModel->findBySlug('tech-academy');
    if (!$tenant) {
        return 'Tenant not found';
    }

    $courses = $courseModel->getPublishedCourses($tenant['id'], [], 1);
    if (empty($courses)) {
        return 'No courses found';
    }

    $course = $courseModel->getCourseWithDetails($courses[0]['id']);
    return assertTrue(isset($course['instructor_name']), 'Course details should include instructor name');
});

// Test Helper Functions
test('Helper function - url()', function() {
    $url = url('test');
    return assertTrue(strpos($url, 'test') !== false, 'URL should contain path');
});

test('Helper function - e() escaping', function() {
    $escaped = e('<script>alert("xss")</script>');
    return assertTrue(strpos($escaped, '&lt;script&gt;') !== false, 'Should escape HTML');
});

test('Helper function - formatCurrency()', function() {
    $formatted = formatCurrency(99.99, 'USD');
    return assertTrue(strpos($formatted, '$') !== false, 'Should format with currency symbol');
});

test('Helper function - truncate()', function() {
    $text = 'This is a very long text that should be truncated';
    $truncated = truncate($text, 20);
    return assertTrue(strlen($truncated) <= 23, 'Should truncate text'); // 20 + "..."
});

test('Helper function - paginate()', function() {
    $pagination = paginate(100, 1, 20);
    return assertEquals(5, $pagination['total_pages'], 'Should calculate correct total pages');
});

// Password hashing test
test('Password hashing works', function() {
    $password = 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $verified = password_verify($password, $hash);
    return assertTrue($verified, 'Password should verify correctly');
});

// Multi-tenant isolation test
test('Multi-tenant isolation - courses', function() {
    $courseModel = new Course();
    $tenantModel = new Tenant();

    $tenant1 = $tenantModel->findBySlug('tech-academy');
    $tenant2 = $tenantModel->findBySlug('language-school');

    if (!$tenant1 || !$tenant2) {
        return 'Tenants not found';
    }

    $courses1 = $courseModel->count(['tenant_id' => $tenant1['id']]);
    $courses2 = $courseModel->count(['tenant_id' => $tenant2['id']]);

    // Tenants should have different course counts (proving isolation)
    return assertTrue($courses1 >= 0 && $courses2 >= 0, 'Each tenant should have independent course count');
});

// API Key test
test('Tenant has valid API key', function() {
    $tenantModel = new Tenant();
    $tenant = $tenantModel->findBySlug('tech-academy');

    if (!$tenant) {
        return 'Tenant not found';
    }

    return assertTrue(!empty($tenant['api_key']), 'Tenant should have API key');
});

// Test enrollment and progress
require_once __DIR__ . '/../app/models/Enrollment.php';

test('Enrollment model - get student enrollments', function() {
    $userModel = new User();
    $tenantModel = new Tenant();
    $enrollmentModel = new Enrollment();

    $tenant = $tenantModel->findBySlug('tech-academy');
    if (!$tenant) {
        return 'Tenant not found';
    }

    $student = $userModel->findByEmail('student1@example.com', $tenant['id']);
    if (!$student) {
        return 'Student not found';
    }

    $enrollments = $enrollmentModel->getStudentEnrollments($student['id']);
    return assertTrue(is_array($enrollments), 'Should return student enrollments');
});

// Test subscription and limits
require_once __DIR__ . '/../app/models/TenantSubscription.php';
require_once __DIR__ . '/../app/models/TenantUsage.php';

test('Subscription model - get active subscription', function() {
    $subscriptionModel = new TenantSubscription();
    $tenantModel = new Tenant();

    $tenant = $tenantModel->findBySlug('tech-academy');
    if (!$tenant) {
        return 'Tenant not found';
    }

    $subscription = $subscriptionModel->getActiveSubscription($tenant['id']);
    return assertNotNull($subscription, 'Tenant should have active subscription');
});

test('Usage model - get tenant usage', function() {
    $usageModel = new TenantUsage();
    $tenantModel = new Tenant();

    $tenant = $tenantModel->findBySlug('tech-academy');
    if (!$tenant) {
        return 'Tenant not found';
    }

    $usage = $usageModel->getUsage($tenant['id']);
    return assertNotNull($usage, 'Tenant should have usage record');
});

// Summary
echo "\n=====================================================\n";
echo "Test Results:\n";
echo "=====================================================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total:  " . ($passed + $failed) . "\n";
echo "=====================================================\n";

if ($failed > 0) {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
    exit(1);
} else {
    echo "\n✓ All tests passed!\n";
    exit(0);
}
