<?php
// FILE: /app/models/User.php

require_once __DIR__ . '/../core/Model.php';

/**
 * User Model
 * Represents all users (platform admins, tenant admins, instructors, students)
 */
class User extends Model
{
    protected $table = 'users';

    /**
     * Find user by email (within tenant or platform admin)
     *
     * @param string $email
     * @param int|null $tenantId
     * @return array|null
     */
    public function findByEmail($email, $tenantId = null)
    {
        if ($tenantId === null) {
            // Platform admin
            $sql = "SELECT * FROM users WHERE email = :email AND tenant_id IS NULL LIMIT 1";
            return $this->queryOne($sql, ['email' => $email]);
        } else {
            return $this->findOne(['email' => $email, 'tenant_id' => $tenantId]);
        }
    }

    /**
     * Create new user
     *
     * @param array $data
     * @return int User ID
     */
    public function createUser($data)
    {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        // Set defaults
        $data['status'] = $data['status'] ?? 'active';
        $data['timezone'] = $data['timezone'] ?? 'UTC';
        $data['language'] = $data['language'] ?? 'en';

        return $this->insert($data);
    }

    /**
     * Verify user password
     *
     * @param string $email
     * @param string $password
     * @param int|null $tenantId
     * @return array|false User data or false
     */
    public function verifyCredentials($email, $password, $tenantId = null)
    {
        $user = $this->findByEmail($email, $tenantId);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        if ($user['status'] !== 'active') {
            return false;
        }

        return $user;
    }

    /**
     * Update last login timestamp
     *
     * @param int $userId
     */
    public function updateLastLogin($userId)
    {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id";
        $this->execute($sql, ['id' => $userId]);
    }

    /**
     * Get users by tenant and role
     *
     * @param int $tenantId
     * @param string|null $role
     * @return array
     */
    public function getUsersByTenant($tenantId, $role = null)
    {
        $conditions = ['tenant_id' => $tenantId];
        if ($role) {
            $conditions['role'] = $role;
        }

        return $this->findAll($conditions, 'created_at DESC');
    }

    /**
     * Get students for a tenant
     *
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getStudents($tenantId, $limit = 20, $offset = 0)
    {
        return $this->findAll(
            ['tenant_id' => $tenantId, 'role' => 'student'],
            'created_at DESC',
            $limit,
            $offset
        );
    }

    /**
     * Count users by tenant and role
     *
     * @param int $tenantId
     * @param string|null $role
     * @return int
     */
    public function countByTenant($tenantId, $role = null)
    {
        $conditions = ['tenant_id' => $tenantId];
        if ($role) {
            $conditions['role'] = $role;
        }

        return $this->count($conditions);
    }

    /**
     * Search users
     *
     * @param int $tenantId
     * @param string $query
     * @param string|null $role
     * @return array
     */
    public function search($tenantId, $query, $role = null)
    {
        $sql = "SELECT * FROM users
                WHERE tenant_id = :tenant_id
                AND (name LIKE :query OR email LIKE :query)";

        $params = [
            'tenant_id' => $tenantId,
            'query' => "%{$query}%"
        ];

        if ($role) {
            $sql .= " AND role = :role";
            $params['role'] = $role;
        }

        $sql .= " ORDER BY name ASC";

        return $this->query($sql, $params);
    }

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($userId, $newPassword)
    {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password_hash' => $passwordHash]);
    }
}
