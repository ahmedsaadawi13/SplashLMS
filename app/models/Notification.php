<?php
// FILE: /app/models/Notification.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Notification Model
 * Simulates email notifications (can be extended with real SMTP later)
 */
class Notification extends Model
{
    protected $table = 'notifications';

    /**
     * Send notification (simulated)
     *
     * @param array $data
     * @return int
     */
    public function send($data)
    {
        $data['status'] = 'sent';
        $data['sent_at'] = date('Y-m-d H:i:s');

        return $this->insert($data);
    }

    /**
     * Queue notification for later sending
     *
     * @param array $data
     * @return int
     */
    public function queue($data)
    {
        $data['status'] = 'pending';

        return $this->insert($data);
    }

    /**
     * Send welcome email to student
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $userName
     * @param string $userEmail
     */
    public function sendWelcomeEmail($tenantId, $userId, $userName, $userEmail)
    {
        $this->send([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'welcome',
            'subject' => 'Welcome to SplashLMS',
            'message' => "Hi {$userName},\n\nWelcome to our learning platform! We're excited to have you on board.\n\nStart exploring courses and begin your learning journey today.\n\nBest regards,\nThe SplashLMS Team"
        ]);
    }

    /**
     * Send enrollment confirmation
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $userName
     * @param string $courseTitle
     */
    public function sendEnrollmentConfirmation($tenantId, $userId, $userName, $courseTitle)
    {
        $this->send([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'enrollment_confirmation',
            'subject' => "You're enrolled in {$courseTitle}",
            'message' => "Hi {$userName},\n\nYou have successfully enrolled in the course: {$courseTitle}\n\nStart learning now and track your progress.\n\nBest regards,\nThe SplashLMS Team"
        ]);
    }

    /**
     * Send course completion notification
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $userName
     * @param string $courseTitle
     */
    public function sendCourseCompletion($tenantId, $userId, $userName, $courseTitle)
    {
        $this->send([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => 'course_completion',
            'subject' => "Congratulations! You completed {$courseTitle}",
            'message' => "Hi {$userName},\n\nCongratulations on completing the course: {$courseTitle}\n\nYour certificate is ready for download.\n\nKeep learning!\n\nBest regards,\nThe SplashLMS Team"
        ]);
    }

    /**
     * Get notifications for user
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserNotifications($userId, $limit = 20)
    {
        return $this->findAll(
            ['user_id' => $userId],
            'created_at DESC',
            $limit
        );
    }
}
