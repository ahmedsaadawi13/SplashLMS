<?php
// FILE: /app/models/Certificate.php

require_once __DIR__ . '/../core/Model.php';

/**
 * Certificate Model
 */
class Certificate extends Model
{
    protected $table = 'certificates';

    /**
     * Get certificate by enrollment
     *
     * @param int $enrollmentId
     * @return array|null
     */
    public function getByEnrollment($enrollmentId)
    {
        return $this->findOne(['enrollment_id' => $enrollmentId]);
    }

    /**
     * Get certificate with details
     *
     * @param int $certificateId
     * @return array|null
     */
    public function getCertificateWithDetails($certificateId)
    {
        $sql = "SELECT c.*, u.name as student_name, co.title as course_title,
                       t.name as tenant_name, i.name as instructor_name
                FROM certificates c
                JOIN users u ON c.student_id = u.id
                JOIN courses co ON c.course_id = co.id
                JOIN tenants t ON c.tenant_id = t.id
                JOIN users i ON co.instructor_id = i.id
                WHERE c.id = :id
                LIMIT 1";

        return $this->queryOne($sql, ['id' => $certificateId]);
    }

    /**
     * Generate certificate for enrollment
     *
     * @param int $enrollmentId
     * @param int $tenantId
     * @param int $studentId
     * @param int $courseId
     * @return int
     */
    public function generateCertificate($enrollmentId, $tenantId, $studentId, $courseId)
    {
        // Check if certificate already exists
        $existing = $this->getByEnrollment($enrollmentId);
        if ($existing) {
            return $existing['id'];
        }

        // Generate unique certificate number
        $certificateNumber = $this->generateCertificateNumber($tenantId);

        $data = [
            'tenant_id' => $tenantId,
            'enrollment_id' => $enrollmentId,
            'student_id' => $studentId,
            'course_id' => $courseId,
            'certificate_number' => $certificateNumber
        ];

        return $this->insert($data);
    }

    /**
     * Generate unique certificate number
     *
     * @param int $tenantId
     * @return string
     */
    private function generateCertificateNumber($tenantId)
    {
        $prefix = 'CERT-' . $tenantId . '-';
        $timestamp = time();
        $random = strtoupper(substr(md5(random_bytes(16)), 0, 6));

        return $prefix . $timestamp . '-' . $random;
    }

    /**
     * Get student certificates
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentCertificates($studentId)
    {
        $sql = "SELECT c.*, co.title as course_title, co.slug as course_slug
                FROM certificates c
                JOIN courses co ON c.course_id = co.id
                WHERE c.student_id = :student_id
                ORDER BY c.issued_at DESC";

        return $this->query($sql, ['student_id' => $studentId]);
    }
}
